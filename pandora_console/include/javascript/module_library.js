/*
Variables from PHP:
- token
- more_details
- total_modules_text
- view_web
- empty_result
- error_get_token
- invalid_user
- error_main
- error_category
- error_categories
- error_no_category
- error_search
*/

const REMOTE_MODULE_LIBRARY_URI = "https://pandorafms.com/library/";
$(document).ready(function() {
  // Save categories in sessionStorage to avoid making the request to the API many times.
  function set_local_categories() {
    var local_categories_exist = sessionStorage.getItem("categories");

    if (local_categories_exist === null) {
      get_all_categories();
    } else {
      // Categories view.
      var local_categories = JSON.parse(atob(local_categories_exist));
      if ($("#categories_library").length) {
        var local_cat = "";
        $.each(local_categories, function(index, value) {
          if (value.count != 0) {
            local_cat +=
              '<div class="card card_category"><a href="index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories&id_cat=' +
              value.id +
              '">' +
              value.name +
              " (" +
              value.count +
              ")</<span></div>";
          }
        });
        $("#categories_library").append(local_cat);
      }

      // Sidebar.
      if (
        $("#categories_sidebar").length &&
        $("#categories_sidebar ul li").length < 1
      ) {
        var first_level = "";
        var second_level = "";
        var parents = [];
        var children = [];

        $.each(local_categories, function(index, value) {
          // First level list - parents.
          if (value.parent == 0 && value.count != 0) {
            first_level +=
              '<li><a class="category_link" href="index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories&id_cat=' +
              value.id +
              '">' +
              value.name +
              " (" +
              value.count +
              ')</a><span id="parent-' +
              value.id +
              '"></span></li>';

            parents.push(value.id);
          } else {
            children.push(value);
          }
        });
        $("#categories_sidebar ul").append(first_level);

        // Second level list - children.
        $.each(parents, function(index, id_parent) {
          $.each(children, function(i, v) {
            if (id_parent == v.parent && v.count != 0) {
              second_level += "<li>"; //'<li id="child-' + v.id + '" class="parent-' + v.parent + '">';
              second_level +=
                '<a class="category_link" href="index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories&id_cat=' +
                v.id +
                '">';
              second_level += v.name + " (" + v.count + ")";
              second_level += "</a>";
              second_level += "</li>";
            }
          });
          $("#categories_sidebar span#parent-" + id_parent).append(
            '<ul class="categories_sidebar_children">' + second_level + "</ul>"
          );
          second_level = "";
        });
      }
    }
  }
  // Call first time.
  set_local_categories();

  // Get all categories from Module library.
  function get_all_categories() {
    $.ajax({
      url: REMOTE_MODULE_LIBRARY_URI + "wp-json/wp/v2/categories?per_page=100",
      type: "GET",
      cache: false,
      crossDomain: true,
      contentType: "application/json",
      success: function(data) {
        sessionStorage.setItem("categories", btoa(JSON.stringify(data)));
        set_local_categories();
      },
      error: function(error) {
        if ($("#categories_library").length) {
          show_error_msg("#categories_library", error_categories);
        }
      }
    });
  }

  // Posts returned from the search / Get all posts from a category.
  function get_modules(search_modules, page, selector) {
    // Pagination.
    if (!page) {
      page = 1;
    }

    var api_url = "";
    if (selector == "search") {
      api_url = REMOTE_MODULE_LIBRARY_URI + "wp-json/wp/v2/posts?search=";
    } else if (selector == "category") {
      api_url = REMOTE_MODULE_LIBRARY_URI + "wp-json/wp/v2/posts/?categories=";
    }

    $.ajax({
      url:
        api_url + search_modules + "&orderby=modified&per_page=9&page=" + page,
      type: "GET",
      cache: false,
      crossDomain: true,
      contentType: "application/json",
      beforeSend: function(xhr) {
        if (token !== null) {
          xhr.setRequestHeader("Authorization", "Bearer " + token);
        }
        $("#" + selector + "_result").addClass("loading_posts");
      },
      success: function(response, textStatus, jqXHR) {
        var total_pages = parseInt(
          jqXHR.getResponseHeader("X-WP-TotalPages"),
          10
        );
        var total_posts = parseInt(jqXHR.getResponseHeader("X-WP-Total"), 10);

        if (selector == "search") {
          $("#search_title_result h2").append(
            "<span class='pandora_green_text' id='search_string'></span>"
          );

          $("#search_string").text(search_modules);
        }

        if (total_posts < 1) {
          $("#" + selector + "_result").css("grid-template-columns", "unset");
          $("#" + selector + "_result").append(
            '<div id="empty_result">' + empty_result + "</div>"
          );
        } else {
          var link = "";
          if (selector == "search") {
            link =
              "index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=search_module&search=" +
              search_modules;
          } else if (selector == "category") {
            link =
              "index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories&id_cat=" +
              search_modules;
          }

          print_excerpt(selector + "_result", response);
          pagination_library(link, total_pages, total_posts, page);
        }
      },
      error: function(error) {
        if (selector == "search") {
          show_error_msg("#search_result", error_search);
        }
        //show_error_msg("#category_result", error_category);
      },
      complete: function(data) {
        $("#" + selector + "_result").removeClass("loading_posts");
      }
    });
  }

  // Show results.
  if ($("#category_result").length || $("#search_result").length) {
    var result = "";
    if ($("#category_result").length) {
      result = "category_result";
      var id_cat = $("#" + result).attr("class");
      id_cat = id_cat.replace("result_category-", "");
    } else if ($("#search_result").length) {
      result = "search_result";
      var search_string = $("#" + result).attr("class");
      search_string = search_string.replace("result_string-", "");
    }

    if ($("#pagination_library").length) {
      var page = $("#pagination_library").attr("class");
      page = page.replace("page-", "");
    }

    if (id_cat != "" && result == "category_result") {
      get_modules(id_cat, page, "category");
      if (result == "category_result") {
        get_category(id_cat);
      }
    } else if (search_string != "" && result == "search_result") {
      get_modules(search_string, page, "search");
    }
  } else if ($("#library_main").length) {
    library_main();
  }

  // Sidebar search.
  $("#search_module").keypress(function(event) {
    var keycode = event.keyCode ? event.keyCode : event.which;
    if (keycode == "13") {
      var browse = $("#search_module").val();
      window.location =
        "index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=search_module&search=" +
        browse;
    }
    event.stopPropagation();
  });
}); // document ready ends

/* Print main page */
function library_main() {
  $.ajax({
    url: REMOTE_MODULE_LIBRARY_URI + "wp-json/wp/v2/pages/121",
    type: "GET",
    cache: false,
    crossDomain: true,
    contentType: "application/json",
    beforeSend: function(xhr) {
      $("#library_main").addClass("loading_posts");
    },
    complete: function(data) {
      $("#library_main").removeClass("loading_posts");
    }
  })
    .done(function(data) {
      var array_cat = [
        "",
        "Miscelaneus",
        "Application monitoring",
        "Network Monitoring",
        "Operating Systems",
        "Artwork",
        "Security monitoring",
        "Tools",
        "Inventory",
        "System Integrations"
      ];

      var array_cat_ids = ["", 4, 5, 7, 6, 9, 8, 11, 12, 13];

      var main_page = data.content.rendered;

      // Remove code from Divi Theme.
      main_page = main_page.replace(/<p>\[.*?\]<\/p>/g, "");

      var clean_page = main_page.split("\n");
      clean_page = clean_page.filter(function(element) {
        return element != "";
      });

      // Title.
      $("#library_main>span").append(clean_page[0]);
      // Description.
      $("#library_main>span+p").append(clean_page[1]);

      // Remove unused items.
      // Remove only 1 index because we need the index 1 to exist for the loop. The 0 index in the loop will be ignored.
      clean_page.splice(0, 1);

      $.each(clean_page, function(i, v) {
        // Clean et_pb_blurb WP tags.
        v = v.replace(/([\[et_pb_blurb].*[\]](?=[A-z]))/g, "");
        var main_category = $(
          "#library_main_content div.library_main_category:nth-child(" + i + ")"
        );

        if (main_category.length > 0) {
          main_category.append(
            "<a href='index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories&id_cat=" +
              array_cat_ids[i] +
              "'><img src='images/module_library/" +
              array_cat[i].replace(" ", "-") +
              ".png'/><h4>" +
              array_cat[i] +
              "</h4>" +
              v +
              "</a>"
          );
        }
      });
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      $("#library_main_content").empty();
      show_error_msg("#library_main_content", error_main);
    });
}

// Get all data from one category. This is necessary to get the category name.
function get_category(id) {
  $.ajax({
    url: REMOTE_MODULE_LIBRARY_URI + "wp-json/wp/v2/categories/" + id,
    type: "GET",
    cache: false,
    contentType: "application/json",
    success: function(response) {
      $("#category_title_result h2").append(
        '<span class="pandora_green_text">' + response.name + "</span>"
      );
    },
    error: function(error) {
      if (error.readyState == 4) {
        if (error.status == "404") {
          show_error_msg("#category_result", error_no_category);
        }
      } else {
        show_error_msg("#category_result", error_category);
      }
    }
  });
}

// Print a summary text of the returned posts (by a category or a search).
function print_excerpt(id_div, response) {
  var return_posts = "";

  $.each(response, function(i, elem) {
    var excerpt = elem.excerpt.rendered;

    if (excerpt.length > 250) {
      excerpt = excerpt.substr(0, 230) + "[&hellip;]</p>";
    }

    return_posts += "<div class='card'>";
    return_posts +=
      "<div class='card_excerpt'><h4>" +
      elem.title.rendered +
      "</h4>" +
      excerpt +
      "</div>";
    return_posts +=
      '<div class="card_link"><div id="card_link-' +
      elem.id +
      '" class="card_link_button"><span>' +
      more_details +
      "</span></div></div>";

    return_posts += "</div>";
  });
  // Append to modal window.
  $("#" + id_div).append(return_posts);

  var modal_details = $("#modal_library").dialog({
    resizable: false,
    autoOpen: false,
    draggable: true,
    modal: true,
    maxHeight: 600,
    width: 700,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    open: function() {
      $(".ui-dialog-content").scrollTop(0);
    },
    buttons: {
      Close: function() {
        modal_details.dialog("close");
      }
    }
  });

  $.each(response, function(i, elem) {
    $("#card_link-" + elem.id).on("click", function() {
      var id = $(this).attr("id");
      id = id.replace("card_link-", "");

      var updated = "";
      var modification_date = new Date(elem.modified).setHours(0, 0, 0, 0);
      var creation_date = new Date(elem.date).setHours(0, 0, 0, 0);
      if (modification_date > creation_date) {
        updated =
          "<p><span class='bold'>Update in: </span><span class='date'>" +
          format_date(elem.modified) +
          "</span></p>";
      }

      if (elem.id == id) {
        $("#modal_library").html(
          '<p><span class="date">' +
            format_date(elem.date) +
            "</span> | <span class='bold'>" +
            category_names(elem.categories) +
            "</span></p>" +
            updated +
            format_download_link(elem.content.rendered) +
            '<div class="view_web"><a href="' +
            elem.link +
            '" target="_blank"><button class="sub next">' +
            view_web +
            "</button></a></div>"
        );
      }
      var title = elem.title.rendered.replace(/<.*?>/g, "");
      modal_details.dialog("option", "title", title).dialog("open");
    });
  });
}

// Function to format date from posts. (To show in modal window).
function format_date(date_string) {
  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec"
  ];

  var date = new Date(date_string);
  let formatted_date =
    months[date.getMonth()] + " " + date.getDate() + ", " + date.getFullYear();

  return formatted_date;
}

// Function to get the names of the categories. (To show in modal window).
function category_names(categories) {
  var local_categories_exist = sessionStorage.getItem("categories");
  if (local_categories_exist !== null) {
    var local_categories = JSON.parse(atob(local_categories_exist));
    var category_names = "";
    $.each(categories, function(index, value) {
      $.each(local_categories, function(i, v) {
        if (value == v.id) {
          category_names +=
            "<a href='index.php?sec=gmodule_library&sec2=godmode/module_library/module_library_view&tab=categories&id_cat=" +
            v.id +
            "'>" +
            v.name +
            "</a>" +
            ", ";
        }
      });
    });
    category_names = category_names.replace(/,\s*$/, "");
    return category_names;
  }
}

// Function to print the pagination.
//?per_page=5&page=4 is equivalent to ?per_page=5&offset=15
function pagination_library(link, total_pages, total_posts, page) {
  $("#pagination_library").append(
    '<div class="pagination_total">' +
      total_modules_text +
      ": " +
      total_posts +
      "</div>"
  );

  // If only one page, don't show pagination.
  if (total_pages <= 1) {
    return;
  }

  var links_pagination = "";
  for (var i = 1; i <= total_pages; i++) {
    if (i == page) {
      links_pagination +=
        '<a href="' +
        link +
        "&page=" +
        i +
        '" class="active_number">' +
        i +
        "</a>";
    } else {
      links_pagination += '<a href="' + link + "&page=" + i + '">' + i + "</a>";
    }
  }
  $("#pagination_library").append(
    '<div class="pagination_pages">' + links_pagination + "</div>"
  );
}

// Show error messsage.
function show_error_msg(selector, message_error) {
  $("#search_result, #category_result").css("grid-template-columns", "unset");
  if (selector == "#categories_library") {
    $(selector).append("<div id='empty_result'>" + message_error + "</div>");
  } else {
    $(selector).append("<div id='empty_result'>" + message_error + "</div>");
  }
}

function format_download_link(html) {
  const regex = /href="(?!\bhttps?:\/\/\b)(?!pandorafms.com)(.*)"/gm;
  var str = html;
  const subst = `href="https://pandorafms.com$1"`;

  // The substituted value will be contained in the result variable
  const result = str.replace(regex, subst);

  return result;
}
