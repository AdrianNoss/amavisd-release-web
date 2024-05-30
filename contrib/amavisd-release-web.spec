#
# Fedora/Enterprise Linux spec file for "amavisd-release-web"
#

# manual build
# rpmbuild -bb --undefine=_disable_source_fetch amavisd-release-web.spec

# manual build with gitcommit
# rpmbuild -bb --undefine=_disable_source_fetch -D "gitcommit <hash>" amavisd-release-web.spec


%global wwwdir /var/www
%global selinux_types %(%{__awk} '/^#[[:space:]]*SELINUXTYPE=/,/^[^#]/ { if ($3 == "-") printf "%s ", $2 }' /etc/selinux/config 2>/dev/null)
%global selinux_variants %([ -z "%{selinux_types}" ] && echo mls targeted || echo %{selinux_types})

Name:      amavisd-release-web
BuildArch: noarch
Version:   1.0.2
Release:   1
Summary:   Web interface to release e-mails from Amavis quarantine
License:   MIT
URL:       https://github.com/AdrianNoss/amavisd-release-web
Group:     Unspecified

Requires:  php-fpm
Requires:  amavis
Requires:  httpd
Requires:  sudo

BuildRequires:    checkpolicy
BuildRequires:    selinux-policy-devel


%if 0%{?gitcommit:1}
Source0:   https://github.com/pbiering/amavisd-release-web/archive/%{gitcommit}/amavisd-release-web-%{gitcommit}.tar.gz
%else
# Temporary until upstream has accepted
Source0:   https://github.com/pbiering/amavisd-release-web/archive/%{version}/amavisd-release-web-%{version}.tar.gz
%endif


%description
Web interface to release e-mails from Amavs quarantine
optional protected by CAPTCHA
- Google reCAPTCHA: https://www.google.com/recaptcha
- hCaptcha: https://dashboard.hcaptcha.com/
- FriendlyCaptcha: https://friendlycaptcha.com/
- Cloudflare Turnstile: https://www.cloudflare.com/products/turnstile/


%package selinux
Summary: SELinux extension for Web interface to release e-mails from Amavis quarantine
Requires: %{name}

%description selinux
SELinux extension for Web interface to release e-mails from Amavs quarantine


%package notify
Summary: Regular notification extension for Web interface to release e-mails from Amavis quarantine
Requires: %{name}

%description notify
Regular notification for Web interface to release e-mails from Amavis quarantine


%prep
%if 0%{?gitcommit:1}
%setup -q -n amavisd-release-web-%{gitcommit}
%else
%setup -q -n amavisd-release-web-%{version}
%endif


%build
# Nothing


%install
install -d %{buildroot}%{wwwdir}/%{name}
install -d %{buildroot}%{wwwdir}/%{name}/css
install -d %{buildroot}%{wwwdir}/%{name}/php
install -d %{buildroot}%{wwwdir}/%{name}/include

%{__cp} css/*.css %{buildroot}%{wwwdir}/%{name}/css
%{__cp} php/*.php %{buildroot}%{wwwdir}/%{name}/php
%{__cp} include/*.php %{buildroot}%{wwwdir}/%{name}/include
%{__cp} index.php %{buildroot}%{wwwdir}/%{name}

%{__cp} include/config.php.example %{buildroot}%{wwwdir}/%{name}/include/config.php


## Package templates
install -d %{buildroot}%{_sysconfdir}/amavisd/
for f in amavis-templ/template*.txt; do
	%{__cp} $f %{buildroot}%{_sysconfdir}/amavisd/amavis-release-web-$(basename $f)
done


## Create required sudo file
install -d %{buildroot}%{_sysconfdir}/sudoers.d/
cat <<END >%{buildroot}%{_sysconfdir}/sudoers.d/99-amavisd-release-web 
apache 		ALL=NOPASSWD:/bin/amavisd-release
END


## Create httpd extension
install -d %{buildroot}%{_sysconfdir}/httpd/conf.d/
cat <<END >%{buildroot}%{_sysconfdir}/httpd/conf.d/amavisd-release-web.conf
## amavisd-release-web config for Apache httpd

Alias /amavisd-release-web /var/www/amavisd-release-web

<Location /amavisd-release-web>
	DirectoryIndex index.php
	Options -Indexes
</Location>
END


## Notify
install -d %{buildroot}%{_sbindir}
install -d %{buildroot}%{_sysconfdir}/cron.d

install -m 750 cron/amavisd-quarantine-notify.pl %{buildroot}%{_sbindir}

# empty configuration file
cat <<END >%{buildroot}%{_sysconfdir}/amavisd/amavisd-release-web-recipients
## whitelist/blacklist of recipients to send regular quarantine notifications
#
#  *** in case nothing is defined not filter is applied ***
#
#  # whitelisted user
#  user@want-to-be-informed.domain.example
#  # whitelisted domain
#  @want-to-be-informed.domain.example
#  # blacklisted user
#  !user@dont-care-about-rejected-spams.domain.example
#  # blacklisted domain
#  !@dont-care-about-rejected-spams.domain.example
#
# remove/adjust next line
@domain.example
END

# cron job
cat <<END >%{buildroot}%{_sysconfdir}/cron.d/amavisd-quarantine-notify.cron
## send regular quarantine status to recipients whitelisted in
## %{_sysconfdir}/amavisd/amavisd-release-web-recipients

## 2-staged notifications

# every 4 hours with history of 5 hours and Spam-Score < 20
34 */4 * * * amavis /usr/sbin/amavisd-quarantine-notify.pl -H 5  -U 20

# every day with history of 25 hours and Spam-Score >= 20
28 6   * * * amavis /usr/sbin/amavisd-quarantine-notify.pl -H 25 -L 20
END


## SELinux
mkdir SELinux

cat <<END >SELinux/amavisd-release-web.te
module amavisd-release-web 1.0;

require {
	type antivirus_t;
	type antivirus_var_run_t;
	type httpd_t;
	type systemd_logind_t;
	type shadow_t;
	class capability { audit_write sys_resource };
	class netlink_audit_socket nlmsg_relay;
	class file { getattr open read };
	class dbus send_msg;
	class sock_file write;
	class unix_stream_socket connectto;
}

#============= httpd_t ==============

#!!!! This avc can be allowed using the boolean 'daemons_enable_cluster_mode'
allow httpd_t antivirus_t:unix_stream_socket connectto;
allow httpd_t antivirus_var_run_t:sock_file write;

#!!!! This avc can be allowed using one of the these booleans:
#     httpd_run_stickshift, httpd_setrlimit
allow httpd_t self:capability { audit_write sys_resource };

#!!!! This avc can be allowed using the boolean 'httpd_mod_auth_pam'
allow httpd_t self:netlink_audit_socket nlmsg_relay;
allow httpd_t shadow_t:file { getattr open read };
allow httpd_t systemd_logind_t:dbus send_msg;
END

for selinuxvariant in %{selinux_variants}
do
    make NAME=${selinuxvariant} -f /usr/share/selinux/devel/Makefile
    %{__mv} %{name}.pp %{name}.pp.${selinuxvariant}
    make NAME=${selinuxvariant} -f /usr/share/selinux/devel/Makefile clean
done

for selinuxvariant in %{selinux_variants}
do 
    install -d %{buildroot}%{_datadir}/selinux/${selinuxvariant}
    install -p -m 644 %{name}.pp.${selinuxvariant} \
        %{buildroot}%{_datadir}/selinux/${selinuxvariant}/%{name}.pp
done

cd -


%post
cat <<'END'
Apply local configurations to %{wwwdir}/%{name}/include/config.php
- $company_url
- $language
- $site_title
- CAPTCHA service configuration

Adjust local 'amavis' configuration related to included notification templates
END


%post selinux
for selinuxvariant in %{selinux_variants}
do
  if rpm -q selinux-policy-$selinuxvariant >/dev/null 2>&1; then
    echo "SELinux semodule store for %{name} ($selinuxvariant)"
    /usr/sbin/semodule -s ${selinuxvariant} -i \
      %{_datadir}/selinux/${selinuxvariant}/%{name}.pp
  else
    echo "SELinux semodule store for %{name} ($selinuxvariant) SKIPPED - policy not installed"
  fi
done

if getsebool httpd_mod_auth_pam | grep -wq "off"; then
  echo "SELinux boolean required to be enabled: httpd_mod_auth_pam"
  setsebool -P httpd_mod_auth_pam=1
else
  echo "SELinux boolean already enabled: httpd_mod_auth_pam"
fi


%postun selinux
if [ $1 -eq 0 ] ; then
  for selinuxvariant in %{selinux_variants}
  do
    if rpm -q selinux-policy-$selinuxvariant >/dev/null 2>&1; then
      echo "SELinux semodule reset %{name} ($selinuxvariant)"
      /usr/sbin/semodule -s ${selinuxvariant} -r %{name}
    else
      echo "SELinux semodule reset %{name} ($selinuxvariant) SKIPPED - policy not installed"
    fi
  done

  if getsebool httpd_mod_auth_pam | grep -wq "on"; then
    echo "SELinux boolean still enabled: httpd_mod_auth_pam"
    echo " Note: potentially enabled during install, disable if no longer required"
    echo " Exec: setsebool -P httpd_mod_auth_pam=0"
  fi
fi



%files
%{wwwdir}/%{name}/css/*.css
%{wwwdir}/%{name}/php/*.php
%{wwwdir}/%{name}/index.php
%{wwwdir}/%{name}/include/lang*.php
%{wwwdir}/%{name}/include/start.php
%{wwwdir}/%{name}/include/functions.php

%{_sysconfdir}/amavisd/amavis-release-web-*.txt

%config(noreplace) %{wwwdir}/%{name}/include/config.php

%config(noreplace) %{_sysconfdir}/sudoers.d/99-amavisd-release-web

%config(noreplace) %{_sysconfdir}/httpd/conf.d/amavisd-release-web.conf


%files selinux
%{_datadir}/selinux/*/%{name}.pp


%files notify
%attr(750,root,amavis) %{_sbindir}/amavisd-quarantine-notify.pl

%config(noreplace) %{_sysconfdir}/amavisd/amavisd-release-web-recipients

%config(noreplace) %{_sysconfdir}/cron.d/amavisd-quarantine-notify.cron


%changelog
* Thu May 30 2024 Peter Bieringer <pb@bieringer.de> - 1.0.2-1
- Release 1.0.2

* Tue Jan 30 2024 Peter Bieringer <pb@bieringer.de> - 1.0.1-3
- Fix post message

* Mon Jan 01 2024 Peter Bieringer <pb@bieringer.de> - 1.0.1-2
- Add notification templates
- Add subpackage "notify"

* Mon Jan 01 2024 Peter Bieringer <pb@bieringer.de> - 1.0.0-1
- Initial release 1.0.0
