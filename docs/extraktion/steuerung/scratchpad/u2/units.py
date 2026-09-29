import json,re,os,sys
S='/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad'
sys.path.insert(0,S)
from auswahl import SKIP,EINLEITUNG
E=json.load(open(S+'/entries.json')); INV=json.load(open(S+'/inv.json'))['files']
LR=json.load(open(S+'/litreadme.json')); CH=LR['chap']; ST=LR['stufe']
L='/home/user/training/docs/literatur/'
ORDER=[ # Zieldatei, Quellen in Tabellenreihenfolge (erste Tabelle je Quelle)
 ('UB',['L-P03','L-P04','L-P05','L-P06','L-A01','L-A02','L-P10','L-P11','L-P12','L-P13','L-P15','L-P16']),
 ('UP',['L-P01','L-P02','L-P07','L-P08','L-P09','L-T2-25','L-T2-26','L-T2-31','L-T2-32']),
 ('T1',['L-T1-02','L-T1-03','L-T1-04','L-T1-05','L-T1-06','L-T1-07','L-T1-08','L-T1-09','L-T1-10','L-T1-12']),
 ('T2',['L-A03','L-T2-03','L-T2-11','L-T2-12','L-T2-04','L-T2-08','L-T2-09','L-T2-10','L-T2-15','L-T2-16','L-T2-17','L-T2-18','L-T2-20','L-T2-21','L-T2-22','L-T2-23','L-T2-24','L-T2-14','L-T2-19','L-T2-27','L-T2-28','L-T2-29','L-T2-30']),
 ('T3',['L-T3-01','L-T3-02','L-T3-03','L-T3-06','L-T3-19','L-T3-09','L-T3-10','L-T3-20','L-T3-21','L-T3-18','L-T3-05']),
 ('R',[f'L-R-{i:02d}' for i in list(range(1,10))+list(range(13,18))+list(range(23,26))+list(range(10,13))+list(range(18,23))+list(range(26,29))]),
 ('T4',[f'L-T4-{i:02d}' for i in (1,2,3,4,5,6,8,10,12,14,16,19,32,34)]),
]
BOOKHINT={'L-T1-08':'Scan (Internet Archive) mit Texterkennung; laut Verzeichnis Druckseite = PDF-Seite des Gesamtbuchs − 2, im Bereich PDF 88–152 − 4 (PDF-Seiten 88–89 wiederholen 86–87; Druckseiten 149–150 fehlen im Scan).',
 'L-T2-04':'Scan mit fehlerhafter Texterkennung (z. B. „ANO“ statt „AND“): Wortlaut und Zahlen am Seitenbild prüfen. Druckseite = PDF-Seite des Gesamtbuchs − 14; PDF-Seiten 577/578 vertauscht.',
 'L-T3-09':'Scan (Internet Archive) mit Texterkennung; Druckseite = PDF-Seite des Gesamtbuchs − 16.',
 'L-T4-32':'E-Book-PDF; Druckseite = PDF-Seite des Gesamtbuchs − 15.','L-T4-34':'Druckseite = PDF-Seite des Gesamtbuchs − 11.',
 'L-A01':'E-Book-PDF, 7. Aufl. 2019 (vorläufig).'}
def zw(e):
    if e.get('zweck'): return e['zweck']
    for k in ('rolle','thema'):
        if e.get(k): return f"{e[k]} (13.2 hat kein Feld zweck; ersatzweise Feld {k})"
    return 'in 13.2 nicht angegeben'
def stufe(i):
    s=E.get(i,{}).get('stufe')
    return (str(s)[0], '13.2') if s and str(s)[0] in 'ABC' else (ST.get(i,'?'),'docs/literatur/README.md (in 13.2 kein Feld stufe)')
def skip(i,nr,titel):
    base=nr.split('-')[0]
    if (i,nr) in EINLEITUNG: return False
    if re.match(r'^9\d',base) or base in ('00','00a'): return True
    return nr in SKIP.get(i,{})
units=[]
for zd,ids in ORDER:
    for i in ids:
        e=E[i]; d=e['datei']; kap=e.get('kapitel'); block=d.split('/')[0]
        stf,stq=stufe(i)
        head=f"- id: {i}\n- stufe: {stf} (Quelle: {stq})\n- zweck: {zw(e)}\n- themenfelder: {e.get('themenfelder') or 'in 13.2 nicht angegeben'}"
        if not kap:
            files=[L+d]; cor=[f for f in INV if f.startswith(block+'/'+i+'_') and 'Corrigendum' in f]
            files+= [L+c for c in cor]
            hint='Artikel: Stelle = Seite laut Zeitschriften-Paginierung, wie sie auf den PDF-Seiten gedruckt ist (fehlt sie, Abschnitt angeben).'
            if i in ('L-T2-29','L-R-18'): hint='Autorenmanuskript: die Seitenzahlen des Manuskripts sind nicht zitierfähig – Stelle als Abschnitt (z. B. „Results, Tab. 2“) angeben, `seiten: –`.'
            if cor: hint+=f' Zweite Eingabedatei ist das Corrigendum zu {i}: im selben Lauf lesen; Aussagen, deren Zahlen das Corrigendum ändert, mit dem korrigierten Wert extrahieren und in der Spalte `zahlen` mit „(korrigiert laut Corrigendum, S. …)“ markieren; im Front matter zusätzlich `corrigendum: {cor[0].split("/")[-1]}`.'
            units.append(dict(zd=zd,id=i,nr='00',block=block,files=files,titel=f"Artikel ({d.split('/')[-1]})",head=head,hint=hint,epub=False,stufe=stf))
        else:
            folder=kap.rstrip('/')
            fl=sorted(f for f in INV if f.startswith(folder+'/'))
            epub=any(f.endswith('.md') for f in fl)
            if epub: fl=[f for f in fl if f.endswith('.md')]
            for f in fl:
                fn=f.split('/')[-1]; nr=re.match(rf'{re.escape(i)}_(\d{{2}}[a-z]?(?:-\d+)?)_',fn).group(1)
                c=CH.get(fn,{}); titel=c.get('titel',fn); cells=c.get('cells',[])
                if skip(i,nr,titel): continue
                if epub:
                    dr=cells[3] if len(cells)>=6 else None
                    hint=(f'EPUB mit Seitenmarken: Druckseiten laut Marken {dr}.' if dr and dr!='–' else 'EPUB ohne Seitenmarken: Stelle als „Kap. <nr>, Abschnitt ‚<Überschrift>‘“ (D-71).')
                    hint+=f' Ansichts-PDF (nur Abbildungen): {L+f[:-3]}.pdf'
                else:
                    pdfr=cells[2] if len(cells)>2 else '?'; dr=cells[3] if len(cells)>=5 else None
                    hint=f'Kapiteldatei-Seite 1 = PDF-Seite {pdfr.split("–")[0]} des Gesamtbuchs (Gesamtbuch-PDF-Seiten {pdfr}).'
                    hint+=(f' Druckseiten laut Verzeichnis: {dr}.' if dr and dr!='–' else ' Druckseiten nicht vorab bestimmt: aus den auf den Seiten gedruckten Seitenzahlen ablesen.')
                    if i in BOOKHINT: hint+=' '+BOOKHINT[i]
                if (i,nr) in EINLEITUNG: hint+=' Die Datei bündelt Vorspann und Einleitung/Foreword: Titelei, Inhaltsverzeichnis, Danksagungen nur unter „Ausgelassen“ nennen; Einleitung/Foreword extrahieren.'
                units.append(dict(zd=zd,id=i,nr=nr,block=block,files=[L+f],titel=titel,head=head,hint=hint,epub=epub,stufe=stf))
for u in units:
    out=f"/home/user/training/docs/extraktion/{u['block']}/{u['id']}/{u['id']}_k{u['nr']}.md"
    u['out']=out
    txt=f"""# Auftrag Extraktion {u['id']} k{u['nr']}

Lies zuerst das Briefing `{S}/u2/brief.md` vollständig und befolge es.

## Eingabedatei(en)
""" + '\n'.join(f'- `{x}`' for x in u['files']) + f"""

## Kapitel
- kapitel: k{u['nr']}
- Titel laut Dateiverzeichnis: {u['titel']}
- Seitenbezug: {u['hint']}

## Quelle aus Konzept 13.2
{u['head']}

## Ausgabe
- `{out}`
"""
    u['auftrag']=f"{S}/u2/auftrag/{u['id']}_k{u['nr']}.md"
    open(u['auftrag'],'w',encoding='utf-8').write(txt)
json.dump(units,open(S+'/u2/units.json','w'),ensure_ascii=False,indent=1)
import collections
print(len(units), collections.Counter(u['zd'] for u in units))
