(function(){'use strict';var f,g=[];function l(a){g.push(a);1==g.length&&f()}function m(){for(;g.length;)g[0](),g.shift()}f=function(){setTimeout(m)};function n(a){this.a=p;this.b=void 0;this.f=[];var b=this;try{a(function(a){q(b,a)},function(a){r(b,a)})}catch(c){r(b,c)}}var p=2;function t(a){return new n(function(b,c){c(a)})}function u(a){return new n(function(b){b(a)})}function q(a,b){if(a.a==p){if(b==a)throw new TypeError;var c=!1;try{var d=b&&b.then;if(null!=b&&"object"==typeof b&&"function"==typeof d){d.call(b,function(b){c||q(a,b);c=!0},function(b){c||r(a,b);c=!0});return}}catch(e){c||r(a,e);return}a.a=0;a.b=b;v(a)}}
function r(a,b){if(a.a==p){if(b==a)throw new TypeError;a.a=1;a.b=b;v(a)}}function v(a){l(function(){if(a.a!=p)for(;a.f.length;){var b=a.f.shift(),c=b[0],d=b[1],e=b[2],b=b[3];try{0==a.a?"function"==typeof c?e(c.call(void 0,a.b)):e(a.b):1==a.a&&("function"==typeof d?e(d.call(void 0,a.b)):b(a.b))}catch(h){b(h)}}})}n.prototype.g=function(a){return this.c(void 0,a)};n.prototype.c=function(a,b){var c=this;return new n(function(d,e){c.f.push([a,b,d,e]);v(c)})};
function w(a){return new n(function(b,c){function d(c){return function(d){h[c]=d;e+=1;e==a.length&&b(h)}}var e=0,h=[];0==a.length&&b(h);for(var k=0;k<a.length;k+=1)u(a[k]).c(d(k),c)})}function x(a){return new n(function(b,c){for(var d=0;d<a.length;d+=1)u(a[d]).c(b,c)})};self.Promise||(self.Promise=n,self.Promise.resolve=u,self.Promise.reject=t,self.Promise.race=x,self.Promise.all=w,self.Promise.prototype.then=n.prototype.c,self.Promise.prototype["catch"]=n.prototype.g);}());
"use strict";

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
(function () {
  'use strict';

  // Adapted from the base64-arraybuffer package implementation
  // (https://github.com/niklasvh/base64-arraybuffer, MIT licensed)
  var CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  var EQ_CHAR = "=".charCodeAt(0);
  // Use a lookup table to find the index.
  var lookup = new Uint8Array(256);
  for (var i = 0; i < CHARS.length; i++) {
    lookup[CHARS.charCodeAt(i)] = i;
  }
  function decode(base64) {
    var len = base64.length;
    var bufferLength = len * 3 >>> 2; // * 0.75
    if (base64.charCodeAt(len - 1) === EQ_CHAR) bufferLength--;
    if (base64.charCodeAt(len - 2) === EQ_CHAR) bufferLength--;
    var bytes = new Uint8Array(bufferLength);
    for (var _i = 0, p = 0; _i < len; _i += 4) {
      var encoded1 = lookup[base64.charCodeAt(_i + 0)];
      var encoded2 = lookup[base64.charCodeAt(_i + 1)];
      var encoded3 = lookup[base64.charCodeAt(_i + 2)];
      var encoded4 = lookup[base64.charCodeAt(_i + 3)];
      bytes[p++] = encoded1 << 2 | encoded2 >> 4;
      bytes[p++] = (encoded2 & 15) << 4 | encoded3 >> 2;
      bytes[p++] = (encoded3 & 3) << 6 | encoded4 & 63;
    }
    return bytes;
  }

  // WARNING: This file was autogenerated by wasmwrap and should not be edited manually.
  var base64 = "AGFzbQEAAAABKghgAABgAn9/AGADf39/AX9gAX8AYAR/f39/AGAAAX9gAX8Bf2ACf38BfwINAQNlbnYFYWJvcnQABAMMCwcGAwAAAQIFAQIABQMBAAEGFgR/AUEAC38BQQALfwBBAwt/AEHgDAsHbgkGbWVtb3J5AgAHX19hbGxvYwABCF9fcmV0YWluAAIJX19yZWxlYXNlAAMJX19jb2xsZWN0AAQHX19yZXNldAAFC19fcnR0aV9iYXNlAwMNVWludDhBcnJheV9JRAMCDHNvbHZlQmxha2UyYgAKCAELCvQSC5IBAQV/IABB8P///wNLBEAACyMBQRBqIgQgAEEPakFwcSICQRAgAkEQSxsiBmoiAj8AIgVBEHQiA0sEQCAFIAIgA2tB//8DakGAgHxxQRB2IgMgBSADShtAAEEASARAIANAAEEASARAAAsLCyACJAEgBEEQayICIAY2AgAgAkEBNgIEIAIgATYCCCACIAA2AgwgBAsEACAACwMAAQsDAAELBgAjACQBC7sCAQF/AkAgAUUNACAAQQA6AAAgACABakEEayICQQA6AAMgAUECTQ0AIABBADoAASAAQQA6AAIgAkEAOgACIAJBADoAASABQQZNDQAgAEEAOgADIAJBADoAACABQQhNDQAgAEEAIABrQQNxIgJqIgBBADYCACAAIAEgAmtBfHEiAmpBHGsiAUEANgIYIAJBCE0NACAAQQA2AgQgAEEANgIIIAFBADYCECABQQA2AhQgAkEYTQ0AIABBADYCDCAAQQA2AhAgAEEANgIUIABBADYCGCABQQA2AgAgAUEANgIEIAFBADYCCCABQQA2AgwgACAAQQRxQRhqIgFqIQAgAiABayEBA0AgAUEgTwRAIABCADcDACAAQgA3AwggAEIANwMQIABCADcDGCABQSBrIQEgAEEgaiEADAELCwsLcgACfyAARQRAQQxBAhABIQALIAALQQA2AgAgAEEANgIEIABBADYCCCABQfD///8DIAJ2SwRAQcAKQfAKQRJBORAAAAsgASACdCIBQQAQASICIAEQBiAAKAIAGiAAIAI2AgAgACACNgIEIAAgATYCCCAAC88BAQJ/QaABQQAQASIAQQxBAxABQYABQQAQBzYCACAAQQxBBBABQQhBAxAHNgIEIABCADcDCCAAQQA2AhAgAEIANwMYIABC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";

  // This is a hand-pruned version of the assemblyscript loader, removing
  // a lot of functionality we don't need, saving in bundle size.
  function addUtilityExports(instance) {
    var extendedExports = {};
    var exports = instance.exports;
    var memory = exports.memory;
    var alloc = exports["__alloc"];
    var retain = exports["__retain"];
    var rttiBase = exports["__rtti_base"] || ~0; // oob if not present
    /** Gets the runtime type info for the given id. */
    function getInfo(id) {
      var U32 = new Uint32Array(memory.buffer);
      // const count = U32[rttiBase >>> 2];
      // if ((id >>>= 0) >= count) throw Error("invalid id: " + id);
      return U32[(rttiBase + 4 >>> 2) + id * 2];
    }
    /** Allocates a new array in the module's memory and returns its retained pointer. */
    extendedExports.__allocArray = function (id, values) {
      var info = getInfo(id);
      var align = 31 - Math.clz32(info >>> 6 & 31);
      var length = values.length;
      var buf = alloc(length << align, 0);
      var arr = alloc(12, id);
      var U32 = new Uint32Array(memory.buffer);
      U32[arr + 0 >>> 2] = retain(buf);
      U32[arr + 4 >>> 2] = buf;
      U32[arr + 8 >>> 2] = length << align;
      var buffer = memory.buffer;
      var view = new Uint8Array(buffer);
      if (info & 1 << 14) {
        for (var _i2 = 0; _i2 < length; ++_i2) view[(buf >>> align) + _i2] = retain(values[_i2]);
      } else {
        view.set(values, buf >>> align);
      }
      return arr;
    };
    extendedExports.__getUint8Array = function (ptr) {
      var U32 = new Uint32Array(memory.buffer);
      var bufPtr = U32[ptr + 4 >>> 2];
      return new Uint8Array(memory.buffer, bufPtr, U32[bufPtr - 4 >>> 2] >>> 0);
    };
    return demangle(exports, extendedExports);
  }
  /** Demangles an AssemblyScript module's exports to a friendly object structure. */
  function demangle(exports) {
    var extendedExports = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    // extendedExports = Object.create(extendedExports);
    var setArgumentsLength = exports["__argumentsLength"] ? function (length) {
      exports["__argumentsLength"].value = length;
    } : exports["__setArgumentsLength"] || exports["__setargc"] || function () {
      return {};
    };
    var _loop = function _loop() {
      if (!Object.prototype.hasOwnProperty.call(exports, internalName)) return 1; // continue
      var elem = exports[internalName];
      // Only necessary if nested exports are present
      //   let parts = internalName.split(".");
      //   let curr = extendedExports;
      //   while (parts.length > 1) {
      //     let part = parts.shift();
      //     if (!Object.prototype.hasOwnProperty.call(curr, part as any)) curr[part as any] = {};
      //     curr = curr[part as any];
      //   }
      var name = internalName.split(".")[0];
      if (typeof elem === "function" && elem !== setArgumentsLength) {
        (extendedExports[name] = function () {
          setArgumentsLength(arguments.length);
          return elem.apply(void 0, arguments);
        }).original = elem;
      } else {
        extendedExports[name] = elem;
      }
    };
    for (var internalName in exports) {
      if (_loop()) continue;
    }
    return extendedExports;
  }
  function instantiateWasmSolver(module) {
    return new Promise(function ($return, $error) {
      var imports, result, exports;
      imports = {
        env: {
          abort: function abort() {
            throw Error("Wasm aborted");
          }
        }
      };
      return Promise.resolve(WebAssembly.instantiate(module, imports)).then(function ($await_7) {
        try {
          result = $await_7;
          exports = addUtilityExports(result);
          return $return({
            exports: exports
          });
        } catch ($boundEx) {
          return $error($boundEx);
        }
      }, $error);
    });
  }
  function getWasmSolver(module) {
    return new Promise(function ($return, $error) {
      var w, arrPtr, solution;
      return Promise.resolve(instantiateWasmSolver(module)).then(function ($await_8) {
        try {
          w = $await_8;
          arrPtr = w.exports.__retain(w.exports.__allocArray(w.exports.Uint8Array_ID, new Uint8Array(128)));
          solution = w.exports.__getUint8Array(arrPtr);
          return $return(function (puzzleBuffer, threshold) {
            var n = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 4294967295;
            solution.set(puzzleBuffer);
            var hashPtr = w.exports.solveBlake2b(arrPtr, threshold, n);
            solution = w.exports.__getUint8Array(arrPtr);
            var hash = w.exports.__getUint8Array(hashPtr);
            w.exports.__release(hashPtr);
            return [solution, hash];
          });
        } catch ($boundEx) {
          return $error($boundEx);
        }
      }, $error);
    });
  }

  // Blake2B made assemblyscript compatible, adapted from (CC0 licensed):
  // Blake2B in pure Javascript
  // Adapted from the reference implementation in RFC7693
  // Ported to Javascript by DC - https://github.com/dcposch
  var Context = /*#__PURE__*/_createClass(function Context(outlen) {
    _classCallCheck(this, Context);
    this.b = new Uint8Array(128);
    this.h = new Uint32Array(16);
    this.t = 0; // input count
    this.c = 0; // pointer within buffer
    this.v = new Uint32Array(32);
    this.m = new Uint32Array(32);
    this.outlen = outlen;
  }); // Little-endian byte access
  function B2B_GET32(arr, i) {
    return arr[i] ^ arr[i + 1] << 8 ^ arr[i + 2] << 16 ^ arr[i + 3] << 24;
  }
  // G Mixing function with everything inlined
  // performance at the cost of readability, especially faster in old browsers
  function B2B_G_FAST(v, m, a, b, c, d, ix, iy) {
    var x0 = m[ix];
    var x1 = m[ix + 1];
    var y0 = m[iy];
    var y1 = m[iy + 1];
    // va0 are the low bits, va1 are the high bits
    var va0 = v[a];
    var va1 = v[a + 1];
    var vb0 = v[b];
    var vb1 = v[b + 1];
    var vc0 = v[c];
    var vc1 = v[c + 1];
    var vd0 = v[d];
    var vd1 = v[d + 1];
    var w0, ww, xor0, xor1;
    // ADD64AA(v, a, b); // v[a,a+1] += v[b,b+1] ... in JS we must store a uint64 as two uint32s
    // ADD64AA(v,a,b)
    w0 = va0 + vb0;
    ww = (va0 & vb0 | (va0 | vb0) & ~w0) >>> 31;
    va0 = w0;
    va1 = va1 + vb1 + ww;
    // // ADD64AC(v, a, x0, x1); // v[a, a+1] += x ... x0 is the low 32 bits of x, x1 is the high 32 bits
    w0 = va0 + x0;
    ww = (va0 & x0 | (va0 | x0) & ~w0) >>> 31;
    va0 = w0;
    va1 = va1 + x1 + ww;
    // v[d,d+1] = (v[d,d+1] xor v[a,a+1]) rotated to the right by 32 bits
    xor0 = vd0 ^ va0;
    xor1 = vd1 ^ va1;
    // We can just swap high and low here becaeuse its a shift by 32 bits
    vd0 = xor1;
    vd1 = xor0;
    // ADD64AA(v, c, d);
    w0 = vc0 + vd0;
    ww = (vc0 & vd0 | (vc0 | vd0) & ~w0) >>> 31;
    vc0 = w0;
    vc1 = vc1 + vd1 + ww;
    // v[b,b+1] = (v[b,b+1] xor v[c,c+1]) rotated right by 24 bits
    xor0 = vb0 ^ vc0;
    xor1 = vb1 ^ vc1;
    vb0 = xor0 >>> 24 ^ xor1 << 8;
    vb1 = xor1 >>> 24 ^ xor0 << 8;
    // ADD64AA(v, a, b);
    w0 = va0 + vb0;
    ww = (va0 & vb0 | (va0 | vb0) & ~w0) >>> 31;
    va0 = w0;
    va1 = va1 + vb1 + ww;
    // ADD64AC(v, a, y0, y1);
    w0 = va0 + y0;
    ww = (va0 & y0 | (va0 | y0) & ~w0) >>> 31;
    va0 = w0;
    va1 = va1 + y1 + ww;
    // v[d,d+1] = (v[d,d+1] xor v[a,a+1]) rotated right by 16 bits
    xor0 = vd0 ^ va0;
    xor1 = vd1 ^ va1;
    vd0 = xor0 >>> 16 ^ xor1 << 16;
    vd1 = xor1 >>> 16 ^ xor0 << 16;
    // ADD64AA(v, c, d);
    w0 = vc0 + vd0;
    ww = (vc0 & vd0 | (vc0 | vd0) & ~w0) >>> 31;
    vc0 = w0;
    vc1 = vc1 + vd1 + ww;
    // v[b,b+1] = (v[b,b+1] xor v[c,c+1]) rotated right by 63 bits
    xor0 = vb0 ^ vc0;
    xor1 = vb1 ^ vc1;
    vb0 = xor1 >>> 31 ^ xor0 << 1;
    vb1 = xor0 >>> 31 ^ xor1 << 1;
    v[a] = va0;
    v[a + 1] = va1;
    v[b] = vb0;
    v[b + 1] = vb1;
    v[c] = vc0;
    v[c + 1] = vc1;
    v[d] = vd0;
    v[d + 1] = vd1;
  }
  // Initialization Vector
  var BLAKE2B_IV32 = [0xf3bcc908, 0x6a09e667, 0x84caa73b, 0xbb67ae85, 0xfe94f82b, 0x3c6ef372, 0x5f1d36f1, 0xa54ff53a, 0xade682d1, 0x510e527f, 0x2b3e6c1f, 0x9b05688c, 0xfb41bd6b, 0x1f83d9ab, 0x137e2179, 0x5be0cd19];
  // Note these offsets have all been multiplied by two to make them offsets into
  // a uint32 buffer.
  var SIGMA82 = [0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 28, 20, 8, 16, 18, 30, 26, 12, 2, 24, 0, 4, 22, 14, 10, 6, 22, 16, 24, 0, 10, 4, 30, 26, 20, 28, 6, 12, 14, 2, 18, 8, 14, 18, 6, 2, 26, 24, 22, 28, 4, 12, 10, 20, 8, 0, 30, 16, 18, 0, 10, 14, 4, 8, 20, 30, 28, 2, 22, 24, 12, 16, 6, 26, 4, 24, 12, 20, 0, 22, 16, 6, 8, 26, 14, 10, 30, 28, 2, 18, 24, 10, 2, 30, 28, 26, 8, 20, 0, 14, 12, 6, 18, 4, 16, 22, 26, 22, 14, 28, 24, 2, 6, 18, 10, 0, 30, 8, 16, 12, 4, 20, 12, 30, 28, 18, 22, 6, 0, 16, 24, 4, 26, 14, 2, 8, 20, 10, 20, 4, 16, 8, 14, 12, 2, 10, 30, 22, 18, 28, 6, 24, 26, 0, 0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 28, 20, 8, 16, 18, 30, 26, 12, 2, 24, 0, 4, 22, 14, 10, 6];
  // Compression function. 'last' flag indicates last block.
  function blake2bCompress(ctx, last) {
    var v = ctx.v;
    var m = ctx.m;
    // init work variables
    for (var _i3 = 0; _i3 < 16; _i3++) {
      v[_i3] = ctx.h[_i3];
      v[_i3 + 16] = BLAKE2B_IV32[_i3];
    }
    // low 64 bits of offset
    v[24] = v[24] ^ ctx.t;
    v[25] = v[25] ^ ctx.t / 0x100000000;
    // high 64 bits not supported, offset may not be higher than 2**53-1
    // last block flag set ?
    if (last) {
      v[28] = ~v[28];
      v[29] = ~v[29];
    }
    // get little-endian words
    for (var _i4 = 0; _i4 < 32; _i4++) {
      m[_i4] = B2B_GET32(ctx.b, 4 * _i4);
    }
    // twelve rounds of mixing
    for (var _i5 = 0; _i5 < 12; _i5++) {
      B2B_G_FAST(v, m, 0, 8, 16, 24, SIGMA82[_i5 * 16 + 0], SIGMA82[_i5 * 16 + 1]);
      B2B_G_FAST(v, m, 2, 10, 18, 26, SIGMA82[_i5 * 16 + 2], SIGMA82[_i5 * 16 + 3]);
      B2B_G_FAST(v, m, 4, 12, 20, 28, SIGMA82[_i5 * 16 + 4], SIGMA82[_i5 * 16 + 5]);
      B2B_G_FAST(v, m, 6, 14, 22, 30, SIGMA82[_i5 * 16 + 6], SIGMA82[_i5 * 16 + 7]);
      B2B_G_FAST(v, m, 0, 10, 20, 30, SIGMA82[_i5 * 16 + 8], SIGMA82[_i5 * 16 + 9]);
      B2B_G_FAST(v, m, 2, 12, 22, 24, SIGMA82[_i5 * 16 + 10], SIGMA82[_i5 * 16 + 11]);
      B2B_G_FAST(v, m, 4, 14, 16, 26, SIGMA82[_i5 * 16 + 12], SIGMA82[_i5 * 16 + 13]);
      B2B_G_FAST(v, m, 6, 8, 18, 28, SIGMA82[_i5 * 16 + 14], SIGMA82[_i5 * 16 + 15]);
    }
    for (var _i6 = 0; _i6 < 16; _i6++) {
      ctx.h[_i6] = ctx.h[_i6] ^ v[_i6] ^ v[_i6 + 16];
    }
  }
  /**
   * FRIENDLY CAPTCHA optimization only, does not reset ctx.t (global byte counter)
   * Assumes no key
   */
  function blake2bResetForShortMessage(ctx, input) {
    // Initialize State vector h with IV
    for (var _i7 = 0; _i7 < 16; _i7++) {
      ctx.h[_i7] = BLAKE2B_IV32[_i7];
    }
    // Danger: These operations and resetting are really only possible because our input is exactly 128 bytes
    ctx.b.set(input);
    // ctx.m.fill(0);
    // ctx.v.fill(0);
    ctx.h[0] ^= 0x01010000 ^ ctx.outlen;
  }

  // This is not an enum to save some bytes in the output bundle.
  var SOLVER_TYPE_JS = 1;
  var SOLVER_TYPE_WASM = 2;
  var CHALLENGE_SIZE_BYTES = 128;
  var HASH_SIZE_BYTES = 32;
  /**
   * Solve the blake2b hashing problem, re-using the memory between different attempts (which solves up to 50% faster).
   *
   * This only changes the last 4 bytes of the input array to find a solution. To find multiple solutions
   * one could call this function multiple times with the 4 bytes in front of those last 4 bytes varying.
   *
   *
   * The goal is to find a nonce that, hashed together with the rest of the input header, has a value of its
   * most significant 32bits that is below some threshold.
   * Approximately this means: the hash value of it starts with K zeroes (little endian), which is expected to be
   * increasingly difficult as K increases.
   *
   * In practice you should ask the client to solve multiple (easier) puzzles which should reduce variance and also allows us
   * to show a progress bar.
   * @param input challenge bytes
   * @param threshold u32 value under which the solution's hash should be below.
   */
  function solveBlake2bEfficient(input, threshold, n) {
    if (input.length != CHALLENGE_SIZE_BYTES) {
      throw Error("Invalid input");
    }
    var buf = input.buffer;
    var view = new DataView(buf);
    var ctx = new Context(HASH_SIZE_BYTES);
    ctx.t = CHALLENGE_SIZE_BYTES;
    var start = view.getUint32(124, true);
    var end = start + n;
    for (var _i8 = start; _i8 < end; _i8++) {
      view.setUint32(124, _i8, true);
      blake2bResetForShortMessage(ctx, input);
      blake2bCompress(ctx, true);
      if (ctx.h[0] < threshold) {
        if (ASC_TARGET == 0) {
          // JS
          return new Uint8Array(ctx.h.buffer);
        }
        //@ts-ignore
        return Uint8Array.wrap(ctx.h.buffer);
      }
    }
    return new Uint8Array(0);
  }
  function getJSSolver() {
    return new Promise(function ($return, $error) {
      return $return(function (puzzleBuffer, threshold) {
        var n = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 4294967295;
        var hash = solveBlake2bEfficient(puzzleBuffer, threshold, n);
        return [puzzleBuffer, hash];
      });
    });
  }
  if (!Uint8Array.prototype.slice) {
    Object.defineProperty(Uint8Array.prototype, "slice", {
      value: function value(begin, end) {
        return new Uint8Array(Array.prototype.slice.call(this, begin, end));
      }
    });
  }
  self.ASC_TARGET = 0;
  // 1 for JS, 2 for WASM
  var solverType;
  // Puzzle consisting of zeroes
  var setSolver;
  var solver = new Promise(function (resolve) {
    return setSolver = resolve;
  });
  self.onerror = function (evt) {
    self.postMessage({
      type: "error",
      message: JSON.stringify(evt)
    });
  };
  self.onmessage = function (evt) {
    return new Promise(function ($return, $error) {
      var data;
      data = evt.data;
      var $Try_1_Post = function $Try_1_Post() {
        try {
          return $return();
        } catch ($boundEx) {
          return $error($boundEx);
        }
      };
      var $Try_1_Catch = function $Try_1_Catch(e) {
        try {
          setTimeout(function () {
            throw e;
          });
          return $Try_1_Post();
        } catch ($boundEx) {
          return $error($boundEx);
        }
      };
      try {
        var $If_3 = function $If_3() {
          return $Try_1_Post();
        };
        /**
         * Compile the WASM and setup the solver.
         * If WASM support is not present, it uses the JS version instead.
         */
        if (data.type === "solver") {
          var $If_4 = function $If_4() {
            self.postMessage({
              type: "ready",
              solver: solverType
            });
            return $If_3.call(this);
          };
          if (data.forceJS) {
            var s;
            solverType = SOLVER_TYPE_JS;
            return Promise.resolve(getJSSolver()).then(function ($await_9) {
              try {
                s = $await_9;
                setSolver(s);
                return $If_4.call(this);
              } catch ($boundEx) {
                return $Try_1_Catch($boundEx);
              }
            }.bind(this), $Try_1_Catch);
          } else {
            var $Try_2_Post = function () {
              try {
                return $If_4.call(this);
              } catch ($boundEx) {
                return $Try_1_Catch($boundEx);
              }
            }.bind(this);
            var $Try_2_Catch = function $Try_2_Catch(e) {
              try {
                {
                  var _s;
                  console.log("FriendlyCaptcha failed to initialize WebAssembly, falling back to Javascript solver: " + e.toString());
                  solverType = SOLVER_TYPE_JS;
                  return Promise.resolve(getJSSolver()).then(function ($await_10) {
                    try {
                      _s = $await_10;
                      setSolver(_s);
                      return $Try_2_Post();
                    } catch ($boundEx) {
                      return $Try_1_Catch($boundEx);
                    }
                  }, $Try_1_Catch);
                }
              } catch ($boundEx) {
                return $Try_1_Catch($boundEx);
              }
            };
            try {
              var module, _s2;
              solverType = SOLVER_TYPE_WASM;
              module = WebAssembly.compile(decode(base64));
              return Promise.resolve(module).then(function ($await_11) {
                try {
                  return Promise.resolve(getWasmSolver($await_11)).then(function ($await_12) {
                    try {
                      _s2 = $await_12;
                      setSolver(_s2);
                      return $Try_2_Post();
                    } catch ($boundEx) {
                      return $Try_2_Catch($boundEx);
                    }
                  }, $Try_2_Catch);
                } catch ($boundEx) {
                  return $Try_2_Catch($boundEx);
                }
              }, $Try_2_Catch);
            } catch (e) {
              $Try_2_Catch(e);
            }
          }
        } else {
          var $If_6 = function $If_6() {
            return $If_3.call(this);
          };
          if (data.type === "start") {
            var solve, totalH, solution, view;
            return Promise.resolve(solver).then(function ($await_13) {
              try {
                solve = $await_13;
                self.postMessage({
                  type: "started"
                });
                totalH = 0;
                // We loop over a uint32 to find as solution, it is technically possible (but extremely unlikely - only possible with very high difficulty) that
                // there is no solution, here we loop over one byte further up too in case that happens.
                for (var b = 0; b < 256; b++) {
                  data.puzzleSolverInput[123] = b;
                  var _solve = solve(data.puzzleSolverInput, data.threshold),
                    _solve2 = _slicedToArray(_solve, 2),
                    _s3 = _solve2[0],
                    hash = _solve2[1];
                  if (hash.length === 0) {
                    // This means 2^32 puzzles were evaluated, which takes a while in a browser!
                    // As we try 256 times, this is not fatal
                    console.warn("FC: Internal error or no solution found");
                    totalH += Math.pow(2, 32) - 1;
                    continue;
                  }
                  solution = _s3;
                  break;
                }
                view = new DataView(solution.slice(-4).buffer);
                totalH += view.getUint32(0, true);
                self.postMessage({
                  type: "done",
                  solution: solution.slice(-8),
                  h: totalH,
                  puzzleIndex: data.puzzleIndex,
                  puzzleNumber: data.puzzleNumber
                });
                return $If_6.call(this);
              } catch ($boundEx) {
                return $Try_1_Catch($boundEx);
              }
            }.bind(this), $Try_1_Catch);
          }
          return $If_6.call(this);
        }
      } catch (e) {
        $Try_1_Catch(e);
      }
    });
  };
})();
