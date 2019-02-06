function clippy_set_help(help_section) {
  document.cookie = "clippy=" + help_section;
}

function clippy_go_link_show_help(link, help_section) {
  document.cookie = "clippy=" + help_section;
  window.location.href = link;
}
