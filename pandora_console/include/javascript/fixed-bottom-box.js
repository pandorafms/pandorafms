/**
 * File: fixed-bottom-box.js
 * Name: fixedBottomBox
 * Dependencies: jQuery
 */

// This module has an Universal Module Definition pattern and its scope is private

(function(name, factory) {
  // AMD
  if (typeof define === "function" && define.amd) {
    define(["jquery"], factory);
  }
  // Node (CommonJS)
  else if (typeof exports === "object") {
    module.exports = factory(require("jquery"));
  }
  // Assign to the jQuery namespace
  else {
    (this.jQuery || this.$)[name] = factory(this.jQuery || this.$);
  }
})("fixedBottomBox", function($) {
  // jQuery required
  if (typeof $ === "undefined") return;
  // Module definition
  return (fixedBottomBox = function(params) {
    var self = new Object();

    self._rendered = false;

    self.isRendered = function(state) {
      if (typeof state === "boolean") self._rendered = state;

      return self._rendered;
    };

    params = params || {};
    self._debug = params.debug || false;
    self._target = params.target || "body";
    self._opened = params.opened || false;
    self._head = params.head || "";
    self._content = params.content || "";
    self._animationDuration = params.animationDuration || 200;

    self._width = params.width || 300;
    self._height = params.height || 400;
    self._headTitle = params.headTitle || "";

    self.setWidth = function(width) {
      if (typeof width !== "number" || width <= 0)
        throw new TypeError("Invalid width");
      self._width = width;

      if (typeof self._box !== "undefined") {
        self._box.head.css("width", width + "px");
        self._box.content.css("width", width + "px");
      }

      return self;
    };

    self.getWidth = function(width) {
      return self._width;
    };

    self.setHeight = function(height) {
      if (typeof height !== "number" || height <= 0)
        throw new TypeError("Invalid height");
      self._heigth = height;

      var headHeight = self._box.head.height() || 30;

      if (typeof self._box !== "undefined") {
        self._box.content.css("max-height", height - headHeight + "px");
      }

      return self;
    };

    self.getHeight = function(height) {
      return self._height;
    };

    self.resize = function(width, height) {
      try {
        self.setWidth(width);
        self.setHeight(height);
        self.emit("resized", { width: width, height: height });
      } catch (error) {
        if (self._debug) console.error(error);
      }

      return self;
    };

    self.render = function(head, content) {
      self._createBox(head, content);

      return self;
    };

    self._renderHead = function(head) {
      head = head || self._head;

      var headBody = $("<div></div>");
      headBody.addClass("fixed-bottom-box-head-body");
      headBody.append(
        $(
          '<span id="fixed-bottom-box-head-title" class="subsection_header_title"></span>'
        )
      );
      var headClose = $("<span></span>");
      headClose.addClass("fixed-bottom-box-head-close").click(function(event) {
        self.close();
      });

      self._box.head = $("<div></div>");
      self._box.head
        .addClass("fixed-bottom-box-head")
        .append(headClose, headBody);
      self._box.append(self._box.head);

      self.emit("head-rendered", {
        headRef: self._box.head,
        headContent: head
      });
    };

    self._renderContent = function(content) {
      content = content || self._content;

      self._box.content = $("<div></div>");
      self._box.content.addClass("fixed-bottom-box-content").append(content);
      self._box.append(self._box.content);

      self.emit("content-rendered", {
        contentRef: self._box.content,
        content: content
      });
    };

    self._createBox = function(head, content) {
      head = head || self._head;
      content = content || self._content;

      if (self.isRendered()) self._destroyBox();

      try {
        self._box = $("<div></div>");
        self._box
          .addClass("fixed-bottom-box")
          .css("position", "fixed")
          .css("top", "150px")
          .css("right", "0")
          .css("width", "25%");

        self._renderHead(head);
        self._renderContent(content);

        self.resize(self._width, self._height);

        if (!self.isOpen()) self._box.content.hide();

        $(self._target).append(self._box);

        self.isRendered(true);

        self.emit("rendered", {
          boxRef: self._box,
          headContent: head,
          content: content
        });
      } catch (error) {
        if (self._debug) console.error(error);
      }
    };

    self._destroyBox = function() {
      try {
        if (self.isRendered()) {
          self._box.hide();

          self._box.content.remove();
          delete self._box.content;

          self._box.head.remove();
          delete self._box.head;

          self._box.remove();
          delete self._box;

          self.isRendered(false);
        }
      } catch (error) {
        if (self._debug) console.error(error);
      }
    };

    self.isOpen = function(state) {
      if (typeof state === "boolean") self._opened = state;

      return self._opened;
    };

    self.setHead = function(head) {
      if (typeof head === "undefined" || head.length <= 0)
        throw new TypeError("Invalid head");
      self._head = head;

      return self;
    };

    self.setContent = function(content) {
      if (typeof content === "undefined" || content.length <= 0)
        throw new TypeError("Invalid content");
      self._content = content;

      return self;
    };

    self.open = function() {
      try {
        if (!self.isOpen()) {
          self._box.content.slideDown(self._animationDuration, function() {
            self.isOpen(true);
            $(this)
              .removeClass("fixed-bottom-box-hided")
              .addClass("fixed-bottom-box-opened");
            self.emit("open", { ref: self });
          });
        }
      } catch (error) {
        if (self._debug) console.error(error);
      }
    };

    self.hide = function() {
      try {
        if (self.isOpen()) {
          self._box.content.slideUp(self._animationDuration, function() {
            self.isOpen(false);
            $(this)
              .removeClass("fixed-bottom-box-opened")
              .addClass("fixed-bottom-box-hided");
            self.emit("hide", { ref: self });
          });
        }
      } catch (error) {
        if (self._debug) console.error(error);
      }
    };

    self.toggle = function() {
      if (self.isOpen()) self.hide();
      else self.open();
    };

    self.close = function() {
      try {
        self._head = "";
        self._content = "";
        self.isOpen(false);
        self._destroyBox();

        self.emit("close", {});
      } catch (error) {
        if (self._debug) console.error(error);
      }
    };

    //-- Event handlers --//

    // Populate the observers
    var eventsList = [
      "open",
      "hide",
      "close",
      "rendered",
      "head-rendered",
      "content-rendered",
      "resized"
    ];
    self._observers = {};
    eventsList.forEach(function(eventName) {
      self._observers[eventName] = [];
    });

    self.emit = function(eventName, payload) {
      if (typeof eventName === "undefined" || eventName.length <= 0)
        throw new TypeError("Invalid event name");
      if (typeof self._observers[eventName] === "undefined")
        throw new TypeError("The introduced event does not exists");
      payload = payload || {};

      var observers = self._observers[eventName];

      observers.forEach(function(callback) {
        if (typeof callback === "function") callback(payload);
      });
    };

    self.on = function(eventName, callback) {
      if (typeof eventName === "undefined" || eventName.length <= 0)
        throw new TypeError("Invalid event name");
      if (typeof callback === "function")
        throw new TypeError("The callback should be a function");

      var res = false;
      if (typeof self._observers[eventName] !== "undefined") {
        var length = self._observers[eventName].push(callback);
        res = length - 1;
      }

      return res;
    };

    // Should receive
    self.onOpen = function(callback) {
      self.on("open", callback);
    };
    // Should receive
    self.onHide = function(callback) {
      self.on("hide", callback);
    };
    // Should receive
    self.onClose = function(callback) {
      self.on("close", callback);
    };
    // Should receive
    self.onHeadRender = function(callback) {
      self.on("head-rendered", callback);
    };
    // Should receive
    self.onContentRender = function(callback) {
      self.on("content-rendered", callback);
    };
    // Should receive
    self.onResize = function(callback) {
      self.on("resized", callback);
    };

    return self;
  });
});
