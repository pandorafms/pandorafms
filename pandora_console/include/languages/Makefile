all: ast.mo  ca.mo  de.mo  es.mo  fr.mo  gl.mo  it.mo  pt_br.mo
	xgettext `find ../../ -name "*\.php"` --output=index.pot  --keyword=__

ast.mo: ast.po
	msgfmt ast.po -o ast.mo

ca.mo: ca.po
	msgfmt ca.po -o ca.mo

de.mo: de.po
	msgfmt de.po -o de.mo

es.mo: es.po
	msgfmt es.po -o es.mo

fr.mo: fr.po
	msgfmt fr.po -o fr.mo

gl.mo: gl.po
	msgfmt gl.po -o gl.mo

it.mo: it.po
	msgfmt it.po -o it.mo

pt_br.mo: pt_br.po
	msgfmt pt_br.po -o pt_br.mo
