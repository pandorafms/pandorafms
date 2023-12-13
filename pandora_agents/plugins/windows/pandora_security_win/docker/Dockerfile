FROM i386/debian

# Update the package list.
RUN apt-get update

# Install needed packages.
RUN apt-get install --yes  \
gnupg2 \
unzip \
software-properties-common \
wget \
xvfb

# Install WineHQ.
RUN wget -q https://dl.winehq.org/wine-builds/winehq.key -O- | apt-key add -
RUN apt-add-repository https://dl.winehq.org/wine-builds/debian/; apt-get update
RUN apt-get install --yes --install-recommends wine

# Use Windows 10.
COPY winetricks /tmp/winetricks
RUN /bin/bash /tmp/winetricks win10; rm -f /tmp/winetricks

# Install Python.
RUN wget https://www.python.org/ftp/python/3.8.10/python-3.8.10.exe
RUN xvfb-run wine python-3.8.10.exe /quiet Include_doc=0 Include_dev=0 Include_test=0 InstallAllUsers=1 PrependPath=1 TargetDir=c:\python; echo
ENV WINEPATH="c:\\python;c:\\python\Scripts"

# Install Python modules..
RUN wine pip.exe install wheel
RUN wine pip.exe install pyinstaller

VOLUME ["/pybuild"]
WORKDIR "/pybuild"
ENTRYPOINT ["/bin/bash", "/pybuild/build.sh"]
