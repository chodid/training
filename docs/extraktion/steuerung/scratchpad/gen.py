import re,json,collections
P='/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad/'
E=json.load(open(P+'entries.json'))
INV=json.load(open(P+'inv.json'))['files']
LR=json.load(open(P+'litreadme.json')); CH=LR['chap']; ST=LR['stufe']
def rng(a,b,pre='L-T4-'): return [f'{pre}{i:02d}' for i in range(a,b+1)]
ZD=[
 ('UB','uebergreifend-belastung-monitoring-erholung',
  ['L-P03','L-P04','L-P05','L-P06','L-A01','L-A02','L-P10','L-P11','L-P12','L-P13','L-P15'],['L-P16']),
 ('UP','uebergreifend-planung-kombiniertes-training',
  ['L-P01','L-P02','L-P07','L-P08','L-P09','L-A01','L-A02','L-T2-25','L-T2-26'],['L-T2-31','L-T2-32']),
 ('T1','t1-ausdauer',
  rng(2,8,'L-T1-')+['L-T1-15'],['L-T1-09','L-T1-10','L-T1-11','L-T1-12','L-T1-14','L-T1-16']),
 ('T2','t2-kraft-haltung',
  ['L-P08','L-A03','L-T2-03','L-T2-11','L-T2-12','L-T2-04','L-T2-08','L-T2-09','L-T2-10']+rng(15,18,'L-T2-')+rng(20,24,'L-T2-'),
  ['L-T2-14','L-T2-19','L-T2-33']+rng(27,30,'L-T2-')),
 ('T3','t3-klettern',
  ['L-T3-01','L-T3-02','L-T3-03','L-T3-06','L-T3-19','L-T3-09','L-T3-10','L-T3-20','L-T3-21','L-T3-16','L-T3-18','L-T3-05'],['L-T3-07']),
 ('R','r-reha-praevention',
  rng(1,9,'L-R-')+rng(13,17,'L-R-')+rng(23,25,'L-R-'),rng(10,12,'L-R-')+rng(18,22,'L-R-')+rng(26,30,'L-R-')),
 ('T4','t4-beweglichkeit',
  ['L-T4-01','L-T4-02','L-T4-03','L-T4-04','L-T4-05','L-T4-06','L-T4-08','L-T4-10','L-T4-12','L-T4-14','L-T4-16','L-T4-17','L-T4-19','L-T4-32','L-T4-34','L-T4-24','L-T4-33'],
  ['L-T4-07','L-T4-09','L-T4-11','L-T4-13','L-T4-15','L-T4-18','L-T4-20','L-T4-21','L-T4-23']+rng(25,31)+['L-T4-35','L-T4-36']),
]
ZDNAME={k:n for k,n,_,_ in ZD}
# Welche Zieldateien nutzen eine Quelle (Aufloesung von Verweisen)
def target(i):
    e=E.get(i,{})
    return e.get('verweis') if e.get('status')=='verweis' else i
uses=collections.defaultdict(list)
for k,n,core,opt in ZD:
    for i in core+opt:
        t=target(i)
        if k not in uses[t]: uses[t].append(k)
LIZENZ=['L-P15','L-T2-15','L-T2-16','L-T2-22','L-T2-23','L-T2-25']
LIZTXT={}
lit=open('docs/literatur/README.md',encoding='utf-8').read()
for i in LIZENZ:
    m=re.search(rf'^\| {i} \|.*?\| (CC [^|;]+?)(?:;|\s*\|)',lit,re.M); LIZTXT[i]=m.group(1).strip() if m else 'keine Angabe'
T1_A02={'02','03-2','03-3','07-1','07-2'}
def stufe(i):
    e=E.get(i,{}); s=e.get('stufe')
    if s and str(s)[0] in 'ABC': return str(s)[0]
    return ST.get(i,'?')
def status(i): return str(E.get(i,{}).get('status','?'))
def files_of(i):
    e=E.get(i,{}); d=e.get('datei'); k=e.get('kapitel')
    return d,k
def durchs(r):
    if 'woerter' in r: return 'Markdown ✓' if r['woerter']>50 else 'Markdown leer'
    s1=r['s1'][2]
    if s1>=15: out='Text ✓'
    elif r.get('s23') and r['s23'][2]>=15: out='S. 1 ohne Text, ab S. 2 ✓'
    else: out='**nicht durchsuchbar**'
    n=r.get('pdfseiten') or 0; le=len(r.get('leer',[]))
    if n and le/n>=1/3 and n>3: out+=f'; {le} von {n} Seiten fast ohne Text'
    return out
def skip(nr,titel):
    base=nr.split('-')[0]
    if re.match(r'^9\d',base): return 'Anhang'
    if base in('00','00a') and ('Vorspann' in titel or 'Front' in titel): return 'Vorspann'
    if base=='00': return 'Vorspann'
    return None
SAMMEL={}
def zd_short(lst): return ', '.join(lst)
rows_by_zd={}
missing_core={}
counts={}
lizrows=[]
first_table={}
for k,n,core,opt in ZD:
    rows=[]; miss=[]; cnt=collections.Counter()
    for kind,lst in (('Kern',core),('optional',opt)):
        for i in lst:
            e=E.get(i,{}); st=status(i)
            if st=='verweis':
                t=e['verweis']
                note=f"Verweis auf {t} ({e.get('rolle','')[:60].rstrip()}…)" if e.get('rolle') else f'Verweis auf {t}'
                if i=='L-T1-15':
                    rows.append(f"| {i} → L-A02 | {k} | L-A02 Kap. 02, 03-2, 03-3, 07-1, 07-2 (Abschnitte laut `kapitel_t1`) | – | siehe Tabelle UB | siehe Tabelle UB | Verweis ({kind}); Kapitelzeilen stehen in 4.1 (UB) mit T1 in „Zieldatei(en)“ |")
                else:
                    ft=first_table.get(t,'?')
                    rows.append(f"| {i} → {t} | {k} | siehe {t} | – | siehe Tabelle {ft} | siehe Tabelle {ft} | Verweis ({kind}); {'optional in R, im T4-Kern als Verweis' if t=='L-R-11' else 'Kern'} |")
                continue
            if i=='L-T3-07':
                rows.append(f"| {i} | {k} | – | – | entfällt | entfällt | optional (Alternative zu L-T3-06, D-31) · B · keine Datei; nicht benötigt, solange L-T3-06 vorliegt |"); continue
            d,kap=files_of(i); stf=stufe(i)
            lz=' · **Lizenz vor Ablage prüfen (Athlet)**' if i in LIZENZ else ''
            if i in first_table:
                rows.append(f"| {i} | {zd_short(uses[i])} | siehe Tabelle {first_table[i]} | – | siehe Tabelle {first_table[i]} | siehe Tabelle {first_table[i]} | {st} · {stf} · {kind}; Zeilen nur einmal geführt |")
                cnt['verweis_zeile']+=1
                continue
            if not d:
                rows.append(f"| {i} | {zd_short(uses[i])} | – | – | – | – | {st} · {stf} · {kind} · **fehlt (Beschaffung, Athlet)**" + (" · blockiert die Synthese nicht (W-10)" if kind=="optional" else "") + " |")
                if kind=='Kern' and st.startswith('ausgewaehlt'): miss.append(i)
                cnt['fehlt_'+kind]+=1
                continue
            first_table[i]=k
            if not kap:
                r=INV[d]
                ex='' 
                extra=''
                if i in ('L-T2-23','L-T2-24'):
                    extra=' · Corrigendum als eigene Datei (Zeile darunter)'
                hint=''
                if i in ('L-T2-29','L-R-18'): hint=' · Autorenmanuskript, Seitenzahlen nicht zitierfähig'
                rows.append(f"| {i} | {zd_short(uses[i])} | `{d.split('/')[-1]}` | {r['pdfseiten']} (PDF) | offen | offen | {st} · {stf} · {kind} · {durchs(r)}{hint}{extra}{lz} |")
                cnt['dateien_'+kind]+=1
                if i in ('L-T2-23','L-T2-24'):
                    cf=[f for f in INV if f.startswith(d.split('/')[0]+'/'+i+'_') and 'Corrigendum' in f][0]
                    rr=INV[cf]
                    rows.append(f"| {i} | {zd_short(uses[i])} | `{cf.split('/')[-1]}` | {rr['pdfseiten']} (PDF) | offen | offen | Corrigendum zu {i} · {durchs(rr)} · Einbindung in U2 klären |")
                    cnt['dateien_'+kind]+=1
                if i in LIZENZ: lizrows.append(i)
                if i=='L-A01': pass
            else:
                folder=kap.rstrip('/')
                fl=sorted(f for f in INV if f.startswith(folder+'/'))
                epub = any(f.endswith('.md') for f in fl)
                if epub: fl=[f for f in fl if f.endswith('.md')]
                tot=sum(INV[f].get('pdfseiten') or 0 for f in fl)
                nicht=[]
                book_hint={'L-A01':'7. Aufl. 2019 vorläufig (D-51); **8./9. Aufl. fehlt (Beschaffung, Athlet)**',
                           'L-T1-08':'Scan; Druckseite = PDF-Seite − 2, im Bereich PDF 88–152 − 4',
                           'L-T2-04':'Scan, fehlerhafte Texterkennung; Druckseite = PDF-Seite − 14; PDF 577/578 vertauscht',
                           'L-T3-09':'Scan; Druckseite = PDF-Seite − 16; 3. Aufl. (Neuauflage ab 03/2027 zusätzlich, D-70)',
                           'L-T4-32':'Druckseite = PDF-Seite − 15','L-T4-34':'Druckseite = PDF-Seite − 11; zusätzlich als EPUB',
                           'L-T3-10':'EPUB → Markdown, keine Seitenmarken (Kapitel/Abschnitt, D-71)','L-T3-19':'EPUB → Markdown, keine Seitenmarken (Kapitel/Abschnitt, D-71)',
                           'L-T3-20':'EPUB → Markdown mit Seitenmarken [S. n]','L-T3-21':'EPUB → Markdown mit Seitenmarken [S. n]; vorläufig (Bestätigung Athlet offen)',
                           }.get(i,'Druckseiten je Kapitel in U2 bestimmen')
                umf=f"{len(fl)} Markdown-Dateien + Ansichts-PDFs" if epub else f"{len(fl)} Kapitel-PDFs, {tot} PDF-Seiten"
                rows.append(f"| **{i}** | {zd_short(uses[i])} | **Ordner `{folder}/`** ({umf}) | – | – | – | {st} · {stf} · {kind} · {book_hint}{lz} |")
                for f in fl:
                    r=INV[f]; fn=f.split('/')[-1]; c=CH.get(fn,{})
                    m=re.match(rf'{re.escape(i)}_(\d{{2}}[a-z]?(?:-\d+)?)_',fn); nr=m.group(1)
                    titel=c.get('titel',fn)
                    cells=c.get('cells',[])
                    if epub:
                        dr=cells[3] if len(cells)>=6 else None
                        seiten=(f"Druck {dr}" if dr and dr!='–' else f"EPUB, ca. {cells[2]} Wörter" if len(cells)>2 else 'EPUB')
                    else:
                        pdfr=cells[2] if len(cells)>2 else '?'
                        dr=cells[3] if len(cells)>=5 else None
                        seiten=f"PDF {pdfr}"+(f"; Druck {dr}" if dr and dr!='–' else '')
                    sk=skip(nr,titel)
                    zds=list(uses[i])
                    if i=='L-A02' and nr in T1_A02: zds=zds+['T1']
                    note=durchs(r)
                    if sk:
                        ext='entfällt'; note=f'nicht zu extrahieren ({sk}) · '+note
                        if re.search(r'Introduction|Foreword',titel): note+=' · enthält Einleitung – Extraktion klären'
                        cnt['nicht_zu_extrahieren']+=1
                    else:
                        ext='offen'; cnt['kapitel_'+kind]+=1
                    rows.append(f"| {i} | {zd_short(zds)} | `{nr}` {titel} | {seiten} | {ext} | {ext} | {note} |")
                if i in LIZENZ: lizrows.append(i)
    if k in ('UB','UP'): miss=['L-A01 (8./9. Aufl.; 7. Aufl. liegt vorläufig vor)']+miss
    missing_core[k]=miss; counts[k]=cnt; rows_by_zd[k]=rows
json.dump({'rows':rows_by_zd,'missing':missing_core,'counts':{k:dict(v) for k,v in counts.items()},'liz':LIZTXT,'uses':uses},open(P+'gen.json','w'),ensure_ascii=False,indent=1)
for k in rows_by_zd: print(k,len(rows_by_zd[k]),dict(counts[k]),missing_core[k])
