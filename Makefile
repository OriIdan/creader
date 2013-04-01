po: 
	xgettext --from-code=UTF-8 *.php
	msgmerge -U locales/he_IL/LC_MESSAGES/messages.po messages.po
	echo Translate file: locales/he_IL/LC_MESSAGES/messages.po
	echo Then use make messages.mo to upload the file
	poedit locales/he_IL/LC_MESSAGES/messages.po &

mo:
	./xecmsmsgfmt
	echo Upload files in locales/he_IL/LC_MESSAGES


