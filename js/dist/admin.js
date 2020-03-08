module.exports=function(t){var n={};function o(r){if(n[r])return n[r].exports;var e=n[r]={i:r,l:!1,exports:{}};return t[r].call(e.exports,e,e.exports,o),e.l=!0,e.exports}return o.m=t,o.c=n,o.d=function(t,n,r){o.o(t,n)||Object.defineProperty(t,n,{enumerable:!0,get:r})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(t,n){if(1&n&&(t=o(t)),8&n)return t;if(4&n&&"object"==typeof t&&t&&t.__esModule)return t;var r=Object.create(null);if(o.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:t}),2&n&&"string"!=typeof t)for(var e in t)o.d(r,e,function(n){return t[n]}.bind(null,e));return r},o.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(n,"a",n),n},o.o=function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},o.p="",o(o.s=8)}([function(t,n){t.exports=flarum.core.compat.app},function(t,n){t.exports=flarum.core.compat["components/Button"]},function(t,n,o){"use strict";function r(t,n){t.prototype=Object.create(n.prototype),t.prototype.constructor=t,t.__proto__=n}o.d(n,"a",(function(){return r}))},function(t,n){t.exports=flarum.core.compat["components/Modal"]},function(t,n){t.exports=flarum.core.compat["components/Switch"]},,,,function(t,n,o){"use strict";o.r(n);var r=o(0),e=o.n(r),u=o(2),a=o(3),i=o.n(a),s=o(1),c=o.n(s),l=o(4),p=o.n(l),d="migratetoflarum-fake-data.admin.generator.",f=function(t){function n(){var n;return(n=t.call(this)||this).bulk=!1,n.userCount=0,n.discussionCount=0,n.postCount=0,n.dirty=!1,n.loading=!1,n}Object(u.a)(n,t);var o=n.prototype;return o.title=function(){return e.a.translator.trans(d+"title")},o.content=function(){var t=this;return m(".Modal-body",[m(".Form-group",[p.a.component({state:this.bulk,onchange:function(n){t.bulk=n},children:e.a.translator.trans(d+"bulk-mode")}),m(".helpText",e.a.translator.trans(d+"bulk-mode-description"))]),m(".Form-group",[m("label",e.a.translator.trans(d+"user-count")),m("input.FormControl",{type:"number",min:"0",value:this.userCount+"",oninput:m.withAttr("value",(function(n){t.userCount=parseInt(n),t.dirty=!0}))})]),m(".Form-group",[m("label",e.a.translator.trans(d+"discussion-count")),m("input.FormControl",{type:"number",min:"0",value:this.discussionCount+"",oninput:m.withAttr("value",(function(n){t.discussionCount=parseInt(n),t.dirty=!0}))})]),m(".Form-group",[m("label",e.a.translator.trans(d+"post-count")),m("input.FormControl",{type:"number",min:"0",value:this.postCount+"",oninput:m.withAttr("value",(function(n){t.postCount=parseInt(n),t.dirty=!0}))})]),m(".Form-group",[c.a.component({disabled:!this.dirty,loading:this.loading,className:"Button Button--primary",children:e.a.translator.trans(d+"send"),onclick:function(){t.loading=!0,e.a.request({url:"/api/fake-data",method:"POST",data:{bulk:t.bulk,user_count:t.userCount,discussion_count:t.discussionCount,post_count:t.postCount}}).then((function(){t.userCount=0,t.discussionCount=0,t.postCount=0,t.dirty=!1,t.loading=!1,m.redraw()})).catch((function(n){throw t.loading=!1,m.redraw(),n}))}})])])},n}(i.a);e.a.initializers.add("migratetoflarum-fake-data",(function(t){t.extensionSettings["migratetoflarum-fake-data"]=function(){return t.modal.show(new f)}}))}]);
//# sourceMappingURL=admin.js.map