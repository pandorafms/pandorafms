INCLUDES = $(PANDORA_CFLAGS) 

bin_PROGRAMS = pandora_agent

pandora_agent_SOURCES = pandora_agent.c 		\
	pandora_util.h pandora_util.c		\
	module_type.h				\
	pandora_config.h pandora_config.c

pandora_agent_LDADD = $(PANDORA_LIBS) 

CONFS = \
pandora_agent.conf

pandoraconfdir = $(sysconfdir)/pandora

install-data-local: $(CONFS)
	@$(mkinstalldirs) $(DESTDIR)$(pandoraconfdir)
	@if test -f $(DESTDIR)$(pandoraconfdir)/pandora_agent.conf ; then \
                echo "$@ will not overwrite existing $(DESTDIR)$(pandoraconfdir)/pandora_agent.conf"; \
        else \
		if test -f $(top_builddir)/pandora_agent.conf ; then \
	                $(INSTALL_DATA) $(top_builddir)/pandora_agent.conf $(DESTDIR)$(pandoraconfdir)/pandora_agent.conf; \
		fi;\
        fi


uninstall-local:
	@if test -f $(DESTDIR)$(pandoraconfdir)/pandora_agent.conf; then \
		echo "rm -f $(DESTDIR)$(pandoraconfdir)/pandora_agent.conf"; \
		rm -f $(DESTDIR)$(pandoraconfdir)/pandora_agent.conf; \
	fi
	@if test -z `ls $(DESTDIR)$(pandoraconfdir)/`; then \
		echo "rm -rf $(DESTDIR)$(pandoraconfdir)"; \
                rm -rf $(DESTDIR)$(pandoraconfdir); \
        fi

EXTRA_DIST = pandora_agent.conf

DISTCLEANFILES =                \
$(CONFS)
