
INCLUDES = $(PANDORA_CFLAGS) \
	$(MYSQL_INC) \
	-DGNOMELOCALEDIR=\""$(datadir)/locale"\" \
	-DGLADEDIR=\""$(gladedir)"\"

bin_PROGRAMS = pandora-applet

pandora_applet_SOURCES = main.c \
	pandora-applet.c pandora-applet.h \
	pandora-setup.c pandora-setup.h \
	pandora-status.c pandora-status.h \
	pandora-status-checker.c pandora-status-checker.h \
	pandora-info-window.c pandora-info-window.h \
	pandora-setup-dialog.c pandora-setup-dialog.h \
	eggtrayicon.c eggtrayicon.h

pandora_applet_LDADD = $(PANDORA_LIBS)  \
        $(MYSQL_LIB)

