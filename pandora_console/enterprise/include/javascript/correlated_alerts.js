/* eslint-disable no-unused-vars */
/* global $, load_modal, generalShowMsg, confirmDialog, uniqId */

function allowDrop(ev) {
  ev.preventDefault();
}

function drag(ev) {
  $("#rules").addClass("show");
  ev.dataTransfer.setData("html", ev.target.outerHTML);
}

function checkAll(st) {
  $(".chk").each(function() {
    $(this).attr("checked", st);
  });
}

function save(item) {
  // If not defined id return.
  var text = item.innerText || item.textContent;
  updateStack($(item).attr("var_id"), text);
}

function getStack() {
  if ($("#rule-stack").val() == undefined) return;
  if (stack == "[]") {
    return [];
  }
  var stack = JSON.parse(atob($("#rule-stack").val()));
  return stack;
}

function setStack(stack) {
  /*eslint no-useless-escape: 0 */
  return $("#rule-stack").val(btoa(JSON.stringify(stack)));
}

function addStack(stack, item) {
  stack.push(item);
  setStack(stack);
}

function prevStack(stack) {
  return stack[stack.length - 1];
}

function getVariableType(id) {
  var stack = getStack();

  if (id == undefined) {
    return "unknown";
  }

  for (var i = 0; i < stack.length; i++) {
    if (typeof stack[i].var_id != undefined && stack[i].var_id == id) {
      if (stack[i - i].type == "modifier") {
        // Modifier.
        return stack[i - 1].id;
      }
      if (stack[i - 2].type == "fields") {
        return stack[i - 2].id;
      }
    }
  }
}

function getBlockState() {
  return $("#block-status").val();
}

function setBlockState(st) {
  return $("#block-status").val(st);
}

function getBlockOrder() {
  return parseInt($("#block-order").val());
}

function increaseBlockOrder(order) {
  return $("#block-order").val(parseInt($("#block-order").val()) + 1);
}

function decreaseBlockOrder(order) {
  return $("#block-order").val(parseInt($("#block-order").val()) - 1);
}

function getRulesItem() {
  var getDivs = [];
  var id;
  var item;
  var order;
  $("#rules")
    .find("div")
    .each(function() {
      id = $(this).attr("id");
      item = document.getElementById(id);
      getDivs.push(item);
    });
  return getDivs;
}

function getRulesItemDiv(div_parent) {
  var getDivs = [];
  var id;
  var item;
  $("#rules")
    .find("div")
    .each(function() {
      id = $(this).attr("id");
      var order = $(this).attr("order");

      if (div_parent == order) {
        item = document.getElementById(id);
        getDivs.push(item);
      }
    });
  if (getDivs[0] != null) {
    var nexo_class = getDivs[0].getAttribute("class");
    if (nexo_class.match(/nexo/g)) {
      return false;
    } else {
      return true;
    }
  } else {
    return true;
  }
}

function getDivsRulesItem() {
  var getDivs = [];
  var id;
  var item;
  var order;
  $("#rules")
    .find("div")
    .each(function() {
      id = $(this).attr("class");
      if (id == "") {
        item = document.getElementById(id);
        getDivs.push(item);
      }
    });
  return getDivs;
}

//Function that delete the last item from stack
function removeLast(item) {
  var stack = getStack();
  var getDivs = getRulesItem();
  getDivs = getDivs[getDivs.length - 1];
  getDivs.remove();

  getDivs = getRulesItem();
  getDivs = getDivs[getDivs.length - 1];
  if (getDivs.getAttribute("name") == "div_parent") {
    getDivs.remove();
  }
  var stack_deleted = stack.pop(item);
  if (stack_deleted.id == "block-end") {
    setBlockState("1");
  }
  if (stack.length != 0) {
    if (stack_deleted) {
      if (getBlockOrder() != 0 && stack_deleted.type == "nexos") {
        decreaseBlockOrder();
      }
      if (getBlockOrder() != 0 && stack_deleted.id == "block-start") {
        setBlockState("0");
      }
      setStack(stack);
      updatePaneStatus();
    }
  } else {
    updatePaneStatus();
    paneCleanup();
  }
}

//Function that delete the last Rule from stack
function removeLastRule() {
  var getDivs = document.getElementById(getBlockOrder());
  getDivs.remove();

  var stack = getStack();
  var total_stack = stack.length;
  while (total_stack != 0) {
    if (stack[total_stack - 1].order == getBlockOrder()) {
      var stack_deleted = stack.pop(stack[total_stack - 1]);
      if (stack_deleted.length != 0) {
        setStack(stack);
        updatePaneStatus();
      }
    }
    total_stack--;
  }

  if (getBlockOrder() != 0) {
    decreaseBlockOrder();
  }

  if (stack.length == 0) {
    paneCleanup();
    return;
  }
  if (stack[stack.length - 1].id == "block-end") {
    setBlockState("0");
    $("#submit-rule").attr("disabled", false);
    updatePaneStatus();
  }
}

function getVariableID() {
  var stack = getStack();
  var item;
  while ((item = stack.pop())) {
    if (item.type == "fields" || item.type == "modifiers") {
      return "var-" + item.value + "-" + getBlockOrder();
    }
  }

  return "";
}

function updateStack(id, value) {
  var stack = getStack();

  for (var i = 0; i < stack.length; i++) {
    if (typeof stack[i].var_id != undefined && stack[i].var_id == id) {
      if (stack[i].type == "fields") {
        stack[i].id = value;
      } else {
        stack[i].value = value;
      }
      break;
    }
  }

  // Update stack.
  setStack(stack);
}

/**
 * Check if an item is already inside current block.
 *
 * @param {Object} item
 */
function checkItemBlock(item) {
  var current_block = getBlockOrder();
  var stack = getStack();
  var obj;

  while ((obj = stack.pop())) {
    if (obj.order < current_block) {
      return true;
    }
    if (obj.id == item.id) {
      return false;
    }
  }

  return true;
}

/**
 * Cleans rules from rule builder.
 */
function paneCleanup() {
  // Clean stack.
  $("#rule-stack").val(btoa("[]"));

  // Clean status.
  $("#block-status").val("0");

  // Reset order.
  $("#block-order").val("0");

  // Cleanup rules.
  $("#rules").empty();

  // Empty stack.

  $(".blocks").removeClass("opacityElements");
  $(".blocks").attr("draggable", true);

  $(".fields").addClass("opacityElements");
  $(".fields").attr("draggable", true);

  $("#block-end").addClass("opacityElements");
  $("#block-end").attr("draggable", false);

  $(".operators").addClass("opacityElements");
  $(".operators").attr("draggable", false);

  $(".variables").addClass("opacityElements");
  $(".variables").attr("draggable", false);

  $(".modifiers").addClass("opacityElements");
  $(".modifiers").attr("draggable", false);

  $(".nexos").addClass("opacityElements");
  $(".nexos").attr("draggable", false);
}

/**
 * Enable or Disabled block items
 */
function enableBlockEnd() {
  var stack = getStack();
  var last_stack = stack[stack.length - 1];
  if (getBlockState() == 0 || last_stack.type == "nexos") {
    $("#block-end").addClass("opacityElements");
    $("#block-end").attr("draggable", false);
  } else {
    $(".blocks").removeClass("opacityElements");
    $(".blocks").attr("draggable", true);
    $("#block-start").addClass("opacityElements");
    $("#block-start").attr("draggable", false);
  }
}

/**
 * Initializes panel.
 */
function paneInitialization() {
  var stack = getStack();
  if (stack == undefined) return;
  updatePaneStatus();
}

/**
 * Updates the panel based on latest field inserted.
 *
 * @param {string} classType
 */
function updatePaneStatus() {
  var stack = getStack();
  if (typeof stack == undefined) {
    return;
  }
  var item = prevStack(stack);
  if (typeof item == undefined) {
    return;
  }

  if (item == undefined) {
    return;
  }

  var classType = item.type;
  if (typeof classType == undefined) {
    return;
  }
  // Next button to submit is disabled
  $("#submit-rule").attr("disabled", true);

  // Disable all.
  $(".blocks").addClass("opacityElements");
  $(".blocks").attr("draggable", false);

  $(".fields").addClass("opacityElements");
  $(".fields").attr("draggable", false);

  $(".operators").addClass("opacityElements");
  $(".operators").attr("draggable", false);

  $(".variables").addClass("opacityElements");
  $(".variables").attr("draggable", false);

  $(".modifiers").addClass("opacityElements");
  $(".modifiers").attr("draggable", false);

  $(".nexos").addClass("opacityElements");
  $(".nexos").attr("draggable", false);

  $("#rules").sortable("disable");
  // Source class type action.
  switch (classType) {
    case "fields":
      $(".operators").removeClass("opacityElements");
      $(".operators").attr("draggable", true);
      break;

    case "operators":
      $(".variables").removeClass("opacityElements");
      $(".variables").attr("draggable", true);
      break;

    case "variables":
      $(".modifiers").removeClass("opacityElements");
      $(".modifiers").attr("draggable", true);

      $(".nexos").removeClass("opacityElements");
      $(".nexos").attr("draggable", true);

      $("#block-end").removeClass("opacityElements");
      $("#block-end").attr("draggable", true);
      break;

    case "modifiers":
      $(".variables").removeClass("opacityElements");
      $(".variables").attr("draggable", true);
      break;

    case "nexos":
      if (getBlockState() == "0") {
        $("#block-start").removeClass("opacityElements");
        $("#block-start").attr("draggable", true);
      } else {
        $(".fields").removeClass("opacityElements");
        $(".fields").attr("draggable", true);
      }
      break;

    case "blocks":
      if (getBlockState() == "0") {
        // After closing a rule, we can concatenate more rules.
        $(".nexos").removeClass("opacityElements");
        $(".nexos").attr("draggable", true);

        // We can submit on completed rule.
        $("#submit-rule").attr("disabled", false);

        // We can move rules now.
        $("#rules").sortable("enable");
      } else {
        // After opening a rule, we can add new fields.
        $(".fields").removeClass("opacityElements");
        $(".fields").attr("draggable", true);
      }
      break;

    default:
      break;
  }
}

function extractFromInput(id) {
  var content = [];
  console.log(id);
  try {
    content = JSON.parse(atob($(id).val()));
    console.log(content);
  } catch (error) {
    console.error(error);
  }

  return content;
}

function moveRules() {
  // loop through the original items...
  $("#rules div.div_parent").each(function() {
    // clone the original items to make their
    // absolute-positioned counterparts...
    var item = $(this);
    var item_clone = item.clone();
    // 'store' the clone for later use...
    item.data("rules_clone", item_clone);
    // set the initial position of the clone
    var position = item.position();
    // console.log(position);
    item_clone.css("left", position.left);
    item_clone.css("top", position.top);

    // append the clone...
    $("#rules_clone").append(item_clone);
  });

  // create our sortable as usual...
  // with some event handler extras...
  $("#rules").sortable({
    // their float positions..!
    start: function(e, ui) {
      // loop through the items, except the one we're
      // currently dragging, and hide it...
      ui.helper.addClass("exclude-me");
      $("#rules div.div_parent:not(.exclude-me)").css("visibility", "visible");

      // get the clone that's under it and hide it...
      //ui.helper.data("clone").hide();
    },

    stop: function(e, ui) {
      // get the item we were just dragging, and
      // its clone, and adjust accordingly...
      $("#rules span.exclude-me").each(function() {
        var item = $(this);
        var clone = item.data(this);
        var position = item.position();
        // move the clone under the item we've just dropped...
        clone.css("left", position.left);
        clone.css("top", position.top);
        clone.show();
        // remove unnecessary class...
        item.removeClass("exclude-me");
      });

      // make sure all our original items are visible again...
      $("#rules div.div_parent").css("visibility", "visible");
      reOrderRules();
    },

    // here's where the magic happens...
    change: function(e, ui) {
      // get all invisible items that are also not placeholders
      // and process them when ordering changes...
      $("#rules div.div_parent:not(.exclude-me .ui-sortable-placeholder)").each(
        function() {
          var item = $(this);
          var clone = item.data("clone");
          var position = item.position();
        }
      );
    }
  });
}

function reOrderRules() {
  //Variables
  var stack_aux = [];
  var changed;
  var array_orders = [];
  var rules = getRulesItem();
  var stack = getStack();
  var nexo_class;
  var old_order;
  var div_nexo = [];
  var item;

  // We check if the rules are new or recharged
  for (i = 0; i < rules.length; i++) {
    if (rules[i] != null) {
      if (rules[i].getAttribute("name") == "div_parent") {
        var new_order = rules[i].getAttribute("id");
        array_orders.push(new_order);
        div_nexo = getRulesItemDiv(new_order);
        if (div_nexo == true) {
          changed = new_order;
        }
      }
    }
  }

  // We check if the first rule has a link, if it has a link we will move it to the corresponding stack
  if (rules[1] != null) {
    nexo_class = rules[1].getAttribute("class");
    if (nexo_class.match(/nexo/g)) {
      item = document.getElementById(rules[1].getAttribute("id"));
      old_order = rules[0].getAttribute("id");
      item.remove();
      rules = getRulesItem();
      item.setAttribute("order", changed);
      $("#rules div#" + changed + "").prepend(item);

      for (var x = 0; x < stack.length; x++) {
        if (stack[x] != null) {
          if (
            (stack[x].type == "nexos" && stack[x].order == old_order) ||
            stack[x].order == parseInt(old_order, 10)
          ) {
            stack[x].order = changed;
            setStack(stack);
            break;
          }
        }
      }
    }
  }

  // We collect the stacks of each order
  for (var i = 0; i < array_orders.length; i++) {
    for (x = 0; x < stack.length; x++) {
      if (array_orders[i] == stack[x].order) {
        stack_aux.push(stack[x]);
      }
    }
  }

  // we assign the orders of the stacks
  var reorder = [];
  for (i = 0; i < array_orders.length; i++) {
    for (x = 0; x < stack_aux.length; x++) {
      if (array_orders[i] == stack_aux[x].order) {
        reorder.push(i);
      }
    }
  }

  // We update the order of stack

  for (i = 0; i < stack_aux.length; i++) {
    stack_aux[i].order = reorder[i];
  }

  // Update Stack
  setStack(stack_aux);
}

function getFieldsSelect() {
  return extractFromInput("#fields-select-content");
}

function getGroupsSelect() {
  return extractFromInput("#groups-select-content");
}

function getSeveritySelect() {
  return extractFromInput("#severity-select-content");
}

function getEventTypesSelect() {
  return extractFromInput("#event-types-select-content");
}

function getTagsTypesSelect() {
  return extractFromInput("#tags-types-select-content");
}

function getAgentsSelect() {
  return extractFromInput("#agents-select-content");
}

function getModulesSelect() {
  return extractFromInput("#modules-select-content");
}
function editMe(obj, type) {
  // Source class type action.
  var getDivs = getRulesItem();
  var content;
  getDivs = getDivs[getDivs.length - 2];
  var var_type = getVariableType($(obj).attr("var_id"));
  if (
    (type == "events" && /group/.test(var_type)) ||
    /severity/.test(var_type) ||
    /event-type/.test(var_type) ||
    /event-tag/.test(var_type)
  ) {
    type = "variables";
  }
  switch (type) {
    case "events":
      var_type = getVariableType($(obj).attr("var_id"));

      selectOnClick(obj, "agents-select-content");
      break;
    case "fields":
      // TODO: Translate;
      var fields = getFieldsSelect();

      // TODO: Remove USE block.
      var stack = getStack();
      var id = $(obj).attr("id");

      var value = $(obj).text();
      var mode = "event";
      if (/^Log/.test(value)) {
        mode = "log";
      }

      selectOnClick(obj, fields, "click-list-elements fields-elements-" + mode);
      break;

    case "operators":
      var operators = [">", "<", ">=", "<=", "==", "!=", "REGEX", "NOT REGEX"];
      selectOnClick(obj, operators, "click-list-elements");
      break;

    case "variables":
      // Common.

      var_type = getVariableType($(obj).attr("var_id"));
      if (
        /group/.test(var_type) &&
        getDivs.innerText != "REGEX" &&
        getDivs.innerText != "NOT REGEX"
      ) {
        // Group content.
        var groups = getGroupsSelect();
        selectOnClick(obj, groups, "click-list-elements variable");
      } else if (
        /severity/.test(var_type) &&
        getDivs.innerText != "REGEX" &&
        getDivs.innerText != "NOT REGEX"
      ) {
        // Severity content.
        var severity = getSeveritySelect();
        selectOnClick(obj, severity, "click-list-elements variable");
      } else if (
        /event-type/.test(var_type) &&
        getDivs.innerText != "REGEX" &&
        getDivs.innerText != "NOT REGEX"
      ) {
        // Severity content.
        var types = getEventTypesSelect();
        selectOnClick(obj, types, "click-list-elements variable");
      } else if (
        /event-tag/.test(var_type) &&
        getDivs.innerText != "REGEX" &&
        getDivs.innerText != "NOT REGEX"
      ) {
        // Severity content.
        var tags = getTagsTypesSelect();
        selectOnClick(obj, tags, "click-list-elements variable");
      } else if (/event-agent/.test(var_type)) {
        // Severity content.
        var agents = getAgentsSelect();
        selectOnClick(obj, agents, "click-list-elements variable");
      } else if (/event-module/.test(var_type)) {
        // Severity content.
        var modules = getModulesSelect();
        selectOnClick(obj, modules, "click-list-elements variable");
      } else {
        // Generic value.
        obj.contentEditable = true;
        $("#rules").sortable("disable");
        $(obj).addClass("inEdit");
        $(obj).focusout(function() {
          save(this);
          this.contentEditable = false;
          if (getBlockState() == 0) {
            $("#rules").sortable("enable");
          }
          $(this).removeClass("inEdit");
        });
      }

      break;

    case "modifiers":
      // TODO:XXX;
      break;

    case "nexos":
      var nexos = ["AND", "NAND", "OR", "NOR", "XOR", "NXOR"];
      selectOnClick(obj, nexos, "click-list-elements");

      break;

    case "blocks":
      // TODO:XXX;
      break;

    default:
      break;
  }
}

function selectOnClick(obj, data, classSelect) {
  var select = $("<select></select>").addClass(classSelect);
  var input_value = $(obj).text();

  $(obj).empty();
  $.each(data, function(index, value) {
    var option;
    if (value.id != undefined) {
      option = $('<option value="' + value.id + '"></option>').text(
        value.title
      );
      if (value.title == input_value) {
        option.attr("selected", true);
      }
    } else {
      option = $('<option value="' + value + '"></option>').text(value);
      if (value == input_value) {
        option.attr("selected", true);
      }
    }

    select.append(option);
  });

  var f = function() {
    var new_value = select.val();
    var new_text = select
      .children()
      .filter("option:selected")
      .text();

    if (/variable/.test($(obj).attr("class"))) {
      updateStack($(obj).attr("var_id"), new_value);
    } else {
      updateStack($(obj).attr("id"), new_value);
    }

    $(obj).empty();
    $(obj).append(new_text);

    if (/^fields-log/.test(new_value)) {
      $(obj).addClass("log");
      $(obj).removeClass("event");
    } else if (/^fields-event/.test(new_value)) {
      $(obj).addClass("event");
      $(obj).removeClass("log");
    }
  };

  select.change(f);
  $(obj).focusout(f);

  $(obj).append(select);

  select.focus();
}

/**
 * Add items to rules following gramatic rules.
 *
 * @param {Object} content DOM object dropped.
 */
function ruleBuilder(content) {
  var stack = getStack();
  var id = $(content).attr("id");
  var latest = prevStack(stack);
  var classType = $(content)
    .attr("class")
    .split(/\s+/)[0];

  var item;

  item = {
    type: classType,
    id: id,
    value: $(content).text(),
    order: getBlockOrder()
  };

  // Control block start.
  if (id == "block-start") {
    if (getBlockState() == "1") {
      console.error("Block already opened");
      return false;
    }
    setBlockState("1");

    if (stack.length > 0) {
      if (latest.type == "nexos") {
        // Can add a block start after a nexus.
        addStack(stack, item);
        return true;
      }
    }

    if (stack.length == 0) {
      addStack(stack, item);
      return true;
    }
  }

  // Control block end.
  if (id == "block-end") {
    if (getBlockState() == "0") {
      console.error("Block already closed");
      return false;
    }

    if (stack.length > 0) {
      // We can only close a block after add a variable.
      if (latest.type == "variables") {
        // Can add a block start after a nexus.
        addStack(stack, item);
        setBlockState("0");
        return true;
      }
    }
  }

  // Control field.
  if (classType == "fields") {
    if (
      stack.length == 0 ||
      latest.id == "block-start" ||
      latest.type == "nexos"
    ) {
      // We can add a field after a block start or a nexo.

      if (checkItemBlock(item)) {
        // We cannot repeat field in this block.
        addStack(stack, item);
        return true;
      }
    }
  }

  // Control operator.
  if (classType == "operators") {
    if (latest.type == "fields" || latest.type == "variables") {
      // We can add a operator after a field or a variable.
      addStack(stack, item);
      return true;
    }
  }

  // Control variables.
  if (classType == "variables") {
    if (latest.type == "operators" || latest.type == "modifiers") {
      var var_id = getVariableID();
      // Localize item to handle edition.
      $(content).attr("var-id", var_id);
      item.var_id = var_id;
      // We can add a operator after a field or a variable.
      addStack(stack, item);
      return true;
    }
  }

  // Control modifiers.
  if (classType == "modifiers") {
    // TODO: Could not be repeated per block!!
    if (checkItemBlock(item)) {
      if (latest.type == "variables") {
        // We can add a operator after a field or a variable.
        addStack(stack, item);
        return true;
      }
    }
  }

  // Control nexos.
  if (classType == "nexos") {
    if (latest.type == "variables" && getBlockState() == "0") {
      // After a variable and at the end of a block (rule).
      // Could be anything.
      increaseBlockOrder();
      item.order = item.order + 1;
      addStack(stack, item);
      return true;
    }

    if (latest.id == "block-end" && getBlockState() == "0") {
      // After a block-end could be anything.
      increaseBlockOrder();
      item.order += 1;
      addStack(stack, item);
      return true;
    }

    if (getBlockState() == "1") {
      // I'm inside a block. Coul only use AND nexos.
      if (id == "nexo-and") {
        addStack(stack, item);
        return true;
      }
    }
  }

  return false;
}
function uniqId() {
  var id = Math.floor(Math.random() * 10000);
  return "r" + id;
}

function drop(ev) {
  var stack = getStack();
  var new_rule = getBlockState() == 0;

  if (stack.length == 0) {
    var div_e = document.createElement("div");
    div_e.setAttribute("id", getBlockOrder());
    div_e.setAttribute("name", "div_parent");
    div_e.setAttribute(
      "class",
      "div_parent target flex ui-sortable exclude-me"
    );
  }
  ev.preventDefault();
  $("#rules").removeClass("show");
  var content = ev.dataTransfer.getData("html");

  // Source Element.
  // Extract ID.
  var id = $(content).attr("id");
  // Ensure rules.
  if (ruleBuilder(content) != true) {
    return;
  }

  // Because we can add Nexos outside a rule,
  // And '(' symbols to start them. But we need
  // to add a 'div_e' only once. BUT if stack
  // is empty, we must create a 'div_e' without
  // NEXUS.
  if (stack.length > 0) {
    new_rule = new_rule && getBlockState() == 0;
  }

  // Extract class type.
  var classType = $(content)
    .attr("class")
    .split(/\s+/)[0];

  // Remove Class.
  content = $(content).removeClass(classType);

  // Change ID for non repeat and use variable change text.
  content = $(content).attr("var_id", prevStack(getStack()).var_id);

  // Update general status.
  updatePaneStatus();

  // Add new id.
  var uniq = uniqId();
  $(content)
    .attr("item-id", $(content).attr("id"))
    .attr("id", uniq)
    .attr("order", getBlockOrder())
    .attr("ondblclick", "editMe(this,'" + classType + "')");

  var data = document.createElement("span");

  content = $(content).prop("outerHTML");
  data.innerHTML = content;
  // If content nexo line break.
  if (new_rule) {
    // New rule.
    div_e = document.createElement("div");
    div_e.setAttribute("id", getBlockOrder());
    div_e.setAttribute("name", "div_parent");
    div_e.setAttribute(
      "class",
      "div_parent target flex ui-sortable exclude-me"
    );

    document.getElementById(ev.target.id).appendChild(div_e);
  } else {
    div_e = document.getElementById(getBlockOrder());
  }

  div_e.append(data);
}

/**
 * Function to add automatically variable item when item selected equals modifiers or operators.
 */
function dropVariables() {
  $("#rules").removeClass("show");
  // Source Element.
  var content;
  content =
    '<div id="variable-text" ondblclick="editMe(this, \'variables\');"class="variables variable field">Double click to assign value</div>';

  // Extract ID.
  var id = $(content).attr("id");
  // Ensure rules.
  if (ruleBuilder(content) != true) {
    return;
  }

  // Extract clas type.
  var classType = $(content)
    .attr("class")
    .split(/\s+/)[0];

  // Remove Class.
  content = $(content).removeClass(classType);

  // Change ID for non repeat and use variable change text.
  content = $(content).attr("var_id", prevStack(getStack()).var_id);
  // Update general status.
  updatePaneStatus();

  // TODO: UPDATE ITEM
  // Add new id.
  var uniq = uniqId();
  content = $(content).attr("id", uniq);

  content = $(content).prop("outerHTML");

  // Create content.
  var data = document.createElement("span");

  content = $(content).prop("outerHTML");

  var div_e = document.getElementById(getBlockOrder());
  // Add source element in content.
  data.innerHTML = content;
  div_e.appendChild(data);
  // Add content to target.
  document.getElementById("rules").appendChild(div_e);
}

function add_alert_action(settings) {
  load_modal({
    target: $("#modal-add-action-form"),
    form: "modal_form_add_actions",
    url: settings.url_ajax,
    modal: {
      title: settings.title,
      cancel: settings.btn_cancel,
      ok: settings.btn_text
    },
    onshow: {
      page: settings.url,
      method: "addAlertActionForm",
      extradata: {
        id: settings.id
      }
    },
    onsubmit: {
      page: settings.url,
      method: "addAlertAction",
      dataType: "json"
    },
    ajax_callback: add_alert_action_acept,
    idMsgCallback: "msg-add-action"
  });
}

function add_alert_action_acept(data, idMsg) {
  if (data.error === 1) {
    console.log(data.text);
    return;
  }

  if ($("#emptyli-al-" + data.id_alert).length > 0) {
    $("#emptyli-al-" + data.id_alert).remove();
  }

  $.ajax({
    method: "post",
    url: data.url,
    data: {
      page: data.page,
      method: "addRowActionAjax",
      id_alert: data.id_alert,
      id_action: data.id_action
    },
    dataType: "html",
    success: function(li) {
      $(".ui-dialog-content").dialog("close");
      $("#ul-al-" + data.id_alert).append(li);
    },
    error: function(error) {
      console.log(error);
    }
  });
}

function delete_alert_action(settings) {
  confirmDialog({
    title: settings.title,
    message: settings.msg,
    onAccept: function() {
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "deleteActionAlert",
          id_alert: settings.id_alert,
          id_action: settings.id_action
        },
        dataType: "json",
        success: function(data) {
          // Delete row table.
          $(
            "#li-al-" + settings.id_alert + "-act-" + settings.id_action
          ).remove();

          var num_row = $("#ul-al-" + settings.id_alert + " li").length;
          if (num_row === 0) {
            var emptyli =
              "<li id='emptyli-al-" +
              settings.id_alert +
              "'>" +
              settings.emptyli +
              "</li>";
            $("#ul-al-" + settings.id_alert).append(emptyli);
          }
        },
        error: function(error) {
          console.log(error);
        }
      });
    }
  });
}

function standby_alert(settings) {
  confirmDialog({
    title: settings.title,
    message: settings.msg,
    onAccept: function() {
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "standByAlert",
          id_alert: settings.id_alert,
          standby: settings.standby
        },
        dataType: "html",
        success: function(data) {
          $("#standby-alert-" + settings.id_alert).empty();
          $("#standby-alert-" + settings.id_alert).append(data);
        },
        error: function(error) {
          console.log(error);
        }
      });
    }
  });
}

function disabled_alert(settings) {
  confirmDialog({
    title: settings.title,
    message: settings.msg,
    onAccept: function() {
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "disabledAlert",
          id_alert: settings.id_alert,
          disabled: settings.disabled
        },
        dataType: "json",
        success: function(data) {
          $("#disabled-alert-" + settings.id_alert).empty();
          $("#disabled-alert-" + settings.id_alert).append(data.disabled);
          $("#status-alert-" + settings.id_alert).empty();
          $("#status-alert-" + settings.id_alert).append(data.status);
        },
        error: function(error) {
          console.log(error);
        }
      });
    }
  });
}

function datetime_picker_callback(set) {
  $("#text-time_from, #text-time_to").timepicker({
    showSecond: true,
    timeFormat: set.timeFormat,
    timeOnlyTitle: set.timeOnlyTitle,
    timeText: set.timeText,
    hourText: set.hourText,
    minuteText: set.minuteText,
    secondText: set.secondText,
    currentText: set.currentText,
    closeText: set.closeText
  });

  $("#text-date_from, #text-date_to").datepicker({
    dateFormat: set.dateFormat
  });

  $.datepicker.setDefaults($.datepicker.regional[set.regional]);
}

function firing_action_change(idAlert, page, url) {
  var action = $("#firing_action_select").val();
  $(".mode_table_firing").empty();
  if (action != -1) {
    $("#firing").removeAttr("disabled");
    draw_table_firing(idAlert, action, page, url);
  }

  return;
}

function draw_table_firing(idAlert, action, page, url) {
  $.ajax({
    method: "post",
    url: url,
    data: {
      page: page,
      method: "createActionTableAjax",
      firing_action: action,
      id_alert: idAlert
    },
    dataType: "html",
    success: function(data) {
      $(".mode_table_firing").append(data);
    },
    error: function(error) {
      console.log(error);
    }
  });
}
