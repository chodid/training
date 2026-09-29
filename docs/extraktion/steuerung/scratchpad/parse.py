import re, yaml, json, sys
s=open('docs/konzept/konzept-ki-personal-trainer.md',encoding='utf-8').read()
start=s.index('## 13.2 Literaturkandidaten'); end=s.index('## 13.3 Bewusst')
sec=s[start:end]
entries={}
errs=[]
for m in re.finditer(r'```yaml\n(.*?)```',sec,re.S):
    try:
        d=yaml.safe_load(m.group(1))
    except Exception as e:
        errs.append(str(e)[:100]); continue
    if isinstance(d,list):
        for e in d:
            if isinstance(e,dict) and 'id' in e: entries[e['id']]=e
print(len(entries),'entries; errors',len(errs), errs[:3], file=sys.stderr)
json.dump(entries,open('/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad/entries.json','w'),ensure_ascii=False,indent=1,default=str)
for k,e in entries.items():
    print(k, e.get('status'), e.get('stufe'), 'datei' if e.get('datei') else '-', 'kap' if e.get('kapitel') else '', '|', str(e.get('zweck',''))[:50], '|', e.get('themenfelder'))
