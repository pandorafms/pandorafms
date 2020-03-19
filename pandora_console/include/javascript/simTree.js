/**
 * IMPORTANT. Official plugin does not allow string ids
 * This modificated one yes.
 */
!(function(e) {
  var t = {};
  function i(s) {
    if (t[s]) return t[s].exports;
    var n = (t[s] = { i: s, l: !1, exports: {} });
    return e[s].call(n.exports, n, n.exports, i), (n.l = !0), n.exports;
  }
  (i.m = e),
    (i.c = t),
    (i.d = function(e, t, s) {
      i.o(e, t) || Object.defineProperty(e, t, { enumerable: !0, get: s });
    }),
    (i.r = function(e) {
      "undefined" != typeof Symbol &&
        Symbol.toStringTag &&
        Object.defineProperty(e, Symbol.toStringTag, { value: "Module" }),
        Object.defineProperty(e, "__esModule", { value: !0 });
    }),
    (i.t = function(e, t) {
      if ((1 & t && (e = i(e)), 8 & t)) return e;
      if (4 & t && "object" == typeof e && e && e.__esModule) return e;
      var s = Object.create(null);
      if (
        (i.r(s),
        Object.defineProperty(s, "default", { enumerable: !0, value: e }),
        2 & t && "string" != typeof e)
      )
        for (var n in e)
          i.d(
            s,
            n,
            function(t) {
              return e[t];
            }.bind(null, n)
          );
      return s;
    }),
    (i.n = function(e) {
      var t =
        e && e.__esModule
          ? function() {
              return e.default;
            }
          : function() {
              return e;
            };
      return i.d(t, "a", t), t;
    }),
    (i.o = function(e, t) {
      return Object.prototype.hasOwnProperty.call(e, t);
    }),
    (i.p = ""),
    i((i.s = 0));
})([
  function(e, t, i) {
    "use strict";
    i.r(t);
    i(1);
    !(function(e, t) {
      if (!e || !e.document) throw new Error("simTree need window");
      !(function(e) {
        var t,
          i = e.document,
          s = {
            linkParent: !1,
            response: {
              name: "name",
              id: "id",
              pid: "pid",
              checked: "checked",
              open: "open",
              disabled: "disabled"
            }
          },
          n = function(e) {
            throw new Error(e);
          },
          a = function(e, t) {
            return e.replace(/\{\{(.+?)\}\}/g, function(e, i) {
              return t[i] ? t[i] : "";
            });
          },
          r = function e(t) {
            if (
              ("undefined" == typeof $ && n("simTreeneed jquery"),
              $.isPlainObject(t))
            ) {
              if ((t.el || n("你没有传el"), !(this instanceof e)))
                return new e(t);
              (this.options = $.extend(!0, {}, s, t)), this.init();
            }
          },
          d = [],
          o = [];
        (r.prototype = {
          version: "0.0.2",
          constructor: r,
          on: function(e, t, i) {
            var s, n;
            return (
              (this.handles[e] = this.handles[e] || []),
              (s = this.handles[e].isTriggered),
              (n = this.handles[e].args),
              $.isFunction(t) &&
                (!0 === i ? (this.handles[e] = [t]) : this.handles[e].push(t),
                s && t.call(this, n)),
              this
            );
          },
          off: function(e) {
            return (this.handles[e] = []), this;
          },
          trigger: function(e, t) {
            var i, s;
            for (
              this.handles[e] = this.handles[e] || [],
                i = 0,
                s = this.handles[e].length,
                this.handles[e].isTriggered = !0,
                this.handles[e].args = t;
              i < s;
              i++
            )
              this.handles[e][i].call(this, t);
          },
          init: function() {
            var e = this.options,
              t = e.data;
            (this.handles = {}),
              (this.$el = $(e.el)),
              (this.data = t),
              this.event(),
              this.render();
          },
          dataCallback: function() {
            var e = arguments;
            1 === e.length ? this.render(e[0]) : this.doRender(e[0], e[1]);
          },
          parse: function(e) {
            var t = this.options,
              i = t.response,
              s = [],
              n = {},
              a = 0,
              r = e.length,
              d = i.id,
              o = i.pid;
            if (t.childNodeAsy) return e;
            for (; a < r; a++) {
              var h = e[a],
                c = h[d];
              if (h.children) return e;
              c && (n[c] = h);
            }
            for (a = 0; a < r; a++) {
              var h = e[a],
                l = h[o],
                u = n[l];
              l && u ? (u.children || (u.children = [])).push(h) : s.push(h);
            }
            return s;
          },
          render: function(e) {
            var e = e || this.data;
            $.isFunction(e) && e({}, this.dataCallback.bind(this)),
              $.isArray(e) && ((e = this.parse(e)), this.doRender(this.$el, e));
          },
          doRender: function(e, t, s) {
            var n,
              r,
              h,
              c,
              l,
              u = this,
              f = this.options,
              p = f.response,
              m = t.length,
              g = 0,
              v = p.id,
              k = p.name,
              s = s || 1,
              C =
                '<i data-type="{{asy}}" class="sim-tree-spread {{spreadIcon}}"></i><a href="javascript:;"><i class="sim-tree-checkbox"></i>{{text}}</a>',
              b = e === this.$el,
              y = $(i.createElement("ul")),
              w = f.childNodeAsy ? "asy" : "";
            for (
              f.check ||
              (C = C.replace('<i class="sim-tree-checkbox"></i>', ""));
              g < m;
              g++
            )
              (n = t[g]),
                (r = i.createElement("li")),
                (c = !!n.children),
                (l = n[p.disabled]),
                (r.innerHTML = a(C, {
                  asy: w,
                  text: n[k],
                  spreadIcon: c ? "sim-icon-r" : "hidden"
                })),
                r.setAttribute("data-level", s),
                r.setAttribute("data-id", n[v]),
                l && r.setAttribute("class", "disabled"),
                (h = $(r)).data("data", n),
                y.append(h),
                c && this.doRender(h, n.children, s + 1),
                n[p.checked] && d.push(n[v]),
                n[p.open] && o.push(n[v]);
            m && e.append(y),
              b
                ? (y.addClass("sim-tree"),
                  this.trigger("done", t),
                  $.each(o, function(e, t) {
                    u.expandNode(t);
                  }),
                  this.setSelected(d))
                : f.childNodeAsy &&
                  (this.hideLoading(e.find(".sim-tree-spread")),
                  y.addClass("show"));
          },
          event: function() {
            var e = this;
            this.$el.off("click").on("click", function(t) {
              var i = $(t.target);
              return (
                i.hasClass("sim-tree-spread") && e.spread.call(e, i),
                i.hasClass("sim-tree-checkbox") && (i = i.parent()),
                "a" === i[0].tagName.toLowerCase() && e.clickNode.call(e, i),
                !1
              );
            }),
              this.$el.on("selectstart", function() {
                return !1;
              }),
              this.options.done && this.on("done", this.options.done),
              this.options.onClick && this.on("click", this.options.onClick),
              this.options.onChange && this.on("change", this.options.onChange),
              this.options.onSearch && this.on("search", this.options.onSearch);
          },
          spread: function(e) {
            e.hasClass("sim-icon-r")
              ? this.doSpread(e, !0)
              : this.doSpread(e, !1);
          },
          showLoading: function(e) {
            e.addClass("sim-loading");
          },
          hideLoading: function(e) {
            e.removeClass("sim-loading");
          },
          doSpread: function(e, t) {
            var i = e.parent(),
              s = i.children("ul"),
              n = i.data("data");
            n.children &&
              (t
                ? (e.removeClass("sim-icon-r").addClass("sim-icon-d"),
                  "asy" === e.data("type") &&
                    $.isFunction(this.data) &&
                    (this.showLoading(e),
                    this.data(i.data("data"), this.dataCallback.bind(this, i)),
                    e.data("type", "")),
                  s.addClass("show"))
                : (e.removeClass("sim-icon-d").addClass("sim-icon-r"),
                  s.removeClass("show")));
          },
          clickNode: function(e) {
            var i,
              s,
              n,
              a = this,
              r = e.parent(),
              d = this.$el.find("li"),
              o = d.length,
              h = 0,
              c = [],
              l = !1;
            if (!r.hasClass("disabled")) {
              if (this.options.check)
                for (
                  l = !0,
                    this.doCheck(e.find(".sim-tree-checkbox")),
                    this.options.linkParent &&
                      ((s = r.children("ul")),
                      (n = s.find(".sim-tree-checkbox")),
                      $.each(n, function() {
                        a.doCheck($(this), r.data("checked"), !0);
                      }));
                  h < o;
                  h++
                )
                  !0 === (i = d.eq(h).data()).checked && c.push(i.data);
              else
                t && t.css("font-weight", "normal"),
                  e.css("font-weight", "bold"),
                  (t = e),
                  (i = r.data("data")),
                  (c = [i]),
                  (l = !this.sels || !(this.sels[0] === i));
              (this.sels = c),
                this.trigger("click", c),
                l && this.trigger("change", c);
            }
          },
          doCheck: function(e, t, i) {
            var s = e.closest("li"),
              n = s.data();
            void 0 === t && (t = !n.checked),
              !0 === t
                ? e.removeClass("sim-tree-semi").addClass("checked")
                : !1 === t
                ? e.removeClass("checked sim-tree-semi")
                : "semi" === t &&
                  e.removeClass("checked").addClass("sim-tree-semi"),
              s.data("checked", t),
              !0 === this.options.linkParent && !i && this.setParentCheck(s);
          },
          setParentCheck: function(e) {
            var t,
              i = e.parent("ul"),
              s = i.parent("li"),
              n = i.children("li"),
              a = s.find(">a .sim-tree-checkbox"),
              r = [],
              d = n.length;
            s.length &&
              (e.find(">a .sim-tree-checkbox").hasClass("sim-tree-semi")
                ? this.doCheck(a, "semi")
                : ($.each(n, function() {
                    !0 === $(this).data("checked") && r.push($(this));
                  }),
                  (t = r.length),
                  d === t && this.doCheck(a, !0),
                  t || this.doCheck(a, !1),
                  t >= 1 && t < d && this.doCheck(a, "semi")));
          },
          search: function(e) {
            if (this.$el) {
              var t,
                i,
                s,
                e = $.trim(e),
                n = this.$el.find("li"),
                a = 0,
                r = n.length,
                d = [],
                o = new RegExp(e, "i");
              for (
                n
                  .hide()
                  .children(".sim-tree-spread")
                  .addClass("hidden");
                a < r;
                a++
              )
                (i = n.eq(a)),
                  (t = i.children("a").text()),
                  (s = i.data("data")),
                  e
                    ? -1 !== t.search(o) &&
                      (1 !== parseInt(i.data("level")) &&
                        this.expandNode(s[this.options.response.pid]),
                      i
                        .parents("li")
                        .add(i)
                        .show(),
                      d.push(i))
                    : (i.show(),
                      s.children &&
                        i.children(".sim-tree-spread").removeClass("hidden"));
              this.trigger("search", e);
            }
          },
          expandNode: function(e) {
            var t = e.addClass ? e : this.$el.find("[data-id='" + e + "']"),
              i = t.data("data"),
              s = i[this.options.response.pid],
              n = t.children(".sim-tree-spread"),
              a = parseInt(t.data("level"));
            i.children &&
              n.length &&
              (n.removeClass("hidden"), this.doSpread(n, !0)),
              1 !== a && this.expandNode(s);
          },
          setSelected: function(e) {
            var t = this,
              i = e,
              s = [],
              n = [];
            ("string" != typeof i && "number" != typeof i) || (i = [i]),
              $.isArray(i) &&
                (this.options.check || (i = [i[0]]),
                $.each(i, function(e, i) {
                  var a = t.$el.find("[data-id='" + i + "']"),
                    r = a.children("a"),
                    d = r.children(".sim-tree-checkbox"),
                    o = a.data("data");
                  if (!a.length) return !0;
                  d.length ? t.doCheck(d, !0) : r.css("font-weight", "bold"),
                    1 !== parseInt(a.data("level")) &&
                      t.expandNode(o[t.options.response.pid]),
                    s.push(o),
                    n.push(a[0]);
                }),
                (t.sels = s),
                t.trigger("click", s));
          },
          getSelected: function() {
            return this.sels;
          },
          disableNode: function(e) {
            var t = this,
              i = e;
            ("string" != typeof i && "number" != typeof i) || (i = [i]),
              $.isArray(i) &&
                $.each(i, function(e, i) {
                  var s = t.$el.find("[data-id='" + i + "']");
                  s.addClass("disabled");
                });
          },
          destroy: function() {
            for (var e in (this.$el.html(""), this)) delete this[e];
          },
          refresh: function(e) {
            this.$el.html(""), this.render(e);
          }
        }),
          (e.simTree = r),
          ($.fn.simTree = function(e) {
            return (e = $.extend(!0, { el: this }, e)), r(e);
          });
      })(e);
    })("undefined" != typeof window ? window : void 0);
  },
  function(e, t, i) {}
]);
