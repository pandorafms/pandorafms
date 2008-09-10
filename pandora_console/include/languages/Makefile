all: ast.mo  ca.mo  de.mo  eu.mo es.mo  fr.mo  gl.mo  it.mo  pl.mo pt_br.mo ru.mo sv.mo zh_CN.mo zh_TW.mo
	xgettext `find ../../ -name "*\.php"` --output=index.pot  --keyword=__ --add-comments=///

ast.mo: ast.po
	msgfmt ast.po -o ast.mo

ca.mo: ca.po
	msgfmt ca.po -o ca.mo

de.mo: de.po
	msgfmt de.po -o de.mo

eu.mo: eu.po
	msgfmt eu.po -o eu.mo

es.mo: es.po
	msgfmt es.po -o es.mo

fr.mo: fr.po
	msgfmt fr.po -o fr.mo

gl.mo: gl.po
	msgfmt gl.po -o gl.mo

it.mo: it.po
	msgfmt it.po -o it.mo

pl.mo: pl.po
	msgfmt pl.po -o pl.mo
        
pt_br.mo: pt_br.po
	msgfmt pt_br.po -o pt_br.mo

ru.mo: ru.po
	msgfmt ru.po -o ru.mo  

sv.mo: sv.po
	msgfmt sv.po -o sv.mo

zh_CN.mo: zh_CN.po
	msgfmt zh_CN.po -o zh_CN.mo  

zh_TW.mo: zh_TW.po
	msgfmt zh_TW.po -o zh_TW.mo
