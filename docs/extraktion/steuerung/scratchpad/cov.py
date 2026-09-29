import json,subprocess
P='/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad/'
inv=json.load(open(P+'inv.json'))
for k,r in inv['files'].items():
    if not k.endswith('.pdf'): continue
    t=subprocess.run(['pdftotext','-layout','docs/literatur/'+k,'-'],capture_output=True,text=True).stdout
    pg=t.split('\f')[:r['pdfseiten']]
    low=[i+1 for i,x in enumerate(pg) if len(''.join(x.split()))<100]
    r['leer']=low
json.dump(inv,open(P+'inv.json','w'),ensure_ascii=False,indent=1)
for k,r in inv['files'].items():
    if k.endswith('.pdf') and r['pdfseiten'] and len(r['leer'])/r['pdfseiten']>0.2: print(k,r['pdfseiten'],len(r['leer']))
