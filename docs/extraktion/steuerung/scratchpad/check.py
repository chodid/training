import re,sys,json,os
S='/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad'
REQ=['quelle','kapitel','kapiteltitel','seiten','stufe','lesemethode','modell','datum']
SECS=['Aussagen','Zahlen und Protokolle','Definitionen','Lücken des Kapitels','Ausgelassen','Offene Stellen']
def check(path,unit=None):
    p=[]
    if not os.path.exists(path): return {'path':path,'fehler':['fehlt']}
    t=open(path,encoding='utf-8').read()
    m=re.match(r'---\n(.*?)\n---',t,re.S)
    d={}
    if not m: p.append('kein front matter')
    else:
        for l in m.group(1).split('\n'):
            mm=re.match(r'^([a-z_]+):\s*(.*)$',l)
            if mm: d[mm.group(1)]=mm.group(2).strip().strip('"')
        for k in REQ:
            if not d.get(k): p.append(f'feld {k} fehlt')
        if d.get('lesemethode') not in ('pdf_nativ','pdftotext_layout','markdown_epub'): p.append(f"lesemethode {d.get('lesemethode')}")
        if d.get('modell')!='opus': p.append(f"modell {d.get('modell')}")
        if unit:
            if d.get('quelle')!=unit['id']: p.append(f"quelle {d.get('quelle')}")
            if d.get('kapitel')!='k'+unit['nr']: p.append(f"kapitel {d.get('kapitel')}")
            if unit['epub'] and d.get('lesemethode')!='markdown_epub': p.append('epub ohne markdown_epub')
    for s in SECS:
        if not re.search(r'^## '+re.escape(s),t,re.M): p.append(f'abschnitt {s} fehlt')
    sec=(re.search(r'^## Aussagen.*?\n(.*?)(?=^## |\Z)',t,re.S|re.M) or [None,''])[1]
    rows=[l for l in sec.split('\n') if re.match(r'^\|\s*\d+\s*\|',l)]
    bad=[l[:60] for l in rows if len(re.findall(r'(?<!\\)\|',l))!=8]
    if bad: p.append(f'{len(bad)} Zeilen mit falscher Spaltenzahl')
    uns=[l for l in rows if not re.search(r'\|\s*(true|false)\s*\|\s*$',l)]
    if uns: p.append(f'{len(uns)} Zeilen ohne true/false')
    nost=[l for l in rows if re.search(r'^\|\s*\d+\s*\|[^|]*\|\s*\|',l)]
    if nost: p.append(f'{len(nost)} Zeilen ohne stelle')
    nu=sum(1 for l in rows if re.search(r'\|\s*true\s*\|\s*$',l))
    offsec=(re.search(r'^## Offene Stellen.*?\n(.*?)(?=^## |\Z)',t,re.S|re.M) or [None,''])[1]
    off=[l for l in offsec.split('\n') if l.strip().startswith('- ') and not re.match(r'^-\s*(keine|–|-)\s*\.?$',l.strip(),re.I)]
    nv='nicht verwertbar' in offsec
    unklar=sum(1 for l in rows if re.search(r'\|\s*unklar\s*\|',l))
    return {'path':path,'aussagen':len(rows),'unsicher':nu,'offene':len(off),'unklar':unklar,'nicht_verwertbar':nv,'seiten':d.get('seiten'),'lesemethode':d.get('lesemethode'),'fehler':p}
if __name__=='__main__':
    units=json.load(open(S+'/u2/units.json'))
    sel=[u for u in units if u['id'] in sys.argv[1:]] if len(sys.argv)>1 else units
    for u in sel:
        r=check(u['out'],u); print(json.dumps(r,ensure_ascii=False))
