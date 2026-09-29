import os,re,subprocess,json
P='/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad/'
L='docs/literatur/'
def pages(f):
    o=subprocess.run(['pdfinfo',f],capture_output=True,text=True).stdout
    m=re.search(r'Pages:\s+(\d+)',o); return int(m.group(1)) if m else None
def txt(f,a,b):
    return subprocess.run(['pdftotext','-layout','-f',str(a),'-l',str(b),f,'-'],capture_output=True,text=True).stdout
def score(t):
    ns=[c for c in t if not c.isspace()]
    al=sum(c.isalpha() for c in ns)
    words=re.findall(r'[A-Za-zÄÖÜäöüß]{3,}',t)
    return len(ns),al,len(words)
res={}
bad_names=[]
for root,dirs,files in os.walk(L):
    for fn in sorted(files):
        f=os.path.join(root,fn)
        if fn=='README.md': continue
        chap=root.endswith('_kapitel')
        if chap:
            idm=re.match(r'^(L-[A-Z0-9]+(?:-\d+)?)_(\d{2}[a-z]?)(?:-(\d+))?_([A-Za-z0-9-]+)\.(pdf|md)$',fn)
        else:
            idm=re.match(r'^(L-[A-Z0-9]+(?:-\d+)?)_([A-Za-z-]+)-(\d{4})_([A-Za-z0-9-]+)(?:_\d+ed)?\.(pdf|epub)$',fn)
        if not idm: bad_names.append(f)
        r={'datei':f[len(L):]}
        if fn.endswith('.pdf'):
            n=pages(f); r['pdfseiten']=n
            s1=score(txt(f,1,1)); r['s1']=s1
            if s1[2]<15:
                s2=score(txt(f,2,min(3,n or 1))); r['s23']=s2
        elif fn.endswith('.md'):
            t=open(f,encoding='utf-8').read()
            body=t.split('---',2)[2] if t.startswith('---') else t
            r['woerter']=len(body.split())
            m=re.search(r'seitenbezug: "(.*)"',t); r['seitenbezug']=m.group(1) if m else None
            sm=[int(x) for x in re.findall(r'\[S\. (\d+)\]',body)]
            r['smarks']=(min(sm),max(sm)) if sm else None
        res[f[len(L):]]=r
json.dump({'files':res,'bad_names':bad_names},open(P+'inv.json','w'),ensure_ascii=False,indent=1)
print('bad names:',bad_names)
for k,r in res.items():
    if 's1' in r and r['s1'][2]<15: print('LOW p1',k,r['s1'],r.get('s23'),r['pdfseiten'])
