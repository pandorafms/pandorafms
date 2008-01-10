dnl @synopsis CHECK_MYSQL_DB
dnl
dnl This macro tries to find the headers and librariess for the
dnl MySQL database to build client applications.
dnl
dnl If includes are found, the variable MYSQL_INC will be set. If
dnl libraries are found, the variable MYSQL_LIB will be set. if no check
dnl was successful, the script exits with a error message.
dnl
dnl @category InstalledPackages
dnl @author Harald Welte <laforge@gnumonks.org>
dnl @version 2006-01-07
dnl @license AllPermissive

AC_DEFUN([CHECK_MYSQL_DB], [

AC_ARG_WITH(mysql,
	[  --with-mysql=PREFIX		Prefix of your MySQL installation],
	[my_prefix=$withval], [my_prefix=])
AC_ARG_WITH(mysql-inc,
	[  --with-mysql-inc=PATH		Path to the include directory of MySQL],
	[my_inc=$withval], [my_inc=])
AC_ARG_WITH(mysql-lib,
	[  --with-mysql-lib=PATH		Path to the libraries of MySQL],
	[my_lib=$withval], [my_lib=])


AC_SUBST(MYSQL_INC)
AC_SUBST(MYSQL_LIB)

if test "$my_prefix" != "no"; then

AC_MSG_CHECKING([for MySQL mysql_config program])
for d in $my_prefix/bin /usr/bin /usr/local/bin /usr/local/mysql/bin /opt/mysql/bin /opt/packages/mysql/bin
do
	if test -x $d/mysql_config
	then
		AC_MSG_RESULT(found mysql_config in $d)
		MYSQL_INC=$($d/mysql_config --include)
		MYSQL_LIB=$($d/mysql_config --libs)
		break
	fi
done

if test "$MYSQL_INC" = ""; then
AC_MSG_RESULT(mysql_config not found)
if test "$my_prefix" != ""; then
   AC_MSG_CHECKING([for MySQL includes in $my_prefix/include])
   if test -f "$my_prefix/include/mysql.h" ; then
      MYSQL_INC="-I$my_prefix/include"
      AC_MSG_RESULT([yes])
   else
      AC_MSG_ERROR(mysql.h not found)
   fi
   AC_MSG_CHECKING([for MySQL libraries in $my_prefix/lib])
   if test -f "$my_prefix/lib/libmysql.so" ; then
      MYSQL_LIB="-L$my_prefix/lib -lmysqlclient"
      AC_MSG_RESULT([yes])
   else
      AC_MSG_ERROR(libmysqlclient.so not found)
   fi
else
  if test "$my_inc" != ""; then
    AC_MSG_CHECKING([for MySQL includes in $my_inc])
    if test -f "$my_inc/mysql.h" ; then
      MYSQL_INC="-I$my_inc"
      AC_MSG_RESULT([yes])
    else
      AC_MSG_ERROR(mysql.h not found)
    fi
  fi
  if test "$my_lib" != ""; then
    AC_MSG_CHECKING([for MySQL libraries in $my_lib])
    if test -f "$my_lib/libmysqlclient.so" ; then
      MYSQL_LIB="-L$my_lib -lmysqlclient"
      AC_MSG_RESULT([yes])
    else
      AC_MSG_ERROR(libmysqlclient.so not found)
    fi
  fi
fi

fi

if test "$MYSQL_INC" = "" ; then
  AC_CHECK_HEADER([mysql.h], [], AC_MSG_ERROR(mysql.h not found))
fi
if test "$MYSQL_LIB" = "" ; then
  AC_CHECK_LIB(mysqlclient, mysql_close, [], AC_MSG_ERROR(libmysqlclient.so not found))
fi

fi

])
