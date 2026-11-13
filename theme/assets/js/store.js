(function () {
  'use strict';

  var cfg = window.STORE_CONFIG || {};
  var api = String(cfg.api || '').replace(/\/+$/, '');
  var state = {

    layer: null,
    loading: false,
    data: null
  };

  function el(id) {
    return document.getElementById(id);
  }

  function text(id, value) {
    var node = el(id);

    if (node) {
      node.textContent = value;
    }
  }

  function escapeHtml(value) {
    return String(value === undefined || value === null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function safeUrl(value) {
    var url = String(value === undefined || value === null ? '' : value).trim();

    if (!url) {
      return '';
    }

    return /^(?:https?:\/\/|\/)/i.test(url) ? url : '';
  }

  function stamp() {
    var now = new Date();
    var pad = function (value) {
      return value < 10 ? '0' + value : '' + value;
    };

    return now.getFullYear()
      + '-' + pad(now.getMonth() + 1)
      + '-' + pad(now.getDate())
      + ' ' + pad(now.getHours())
      + ':' + pad(now.getMinutes())
      + ':' + pad(now.getSeconds());
  }

  function setLoading(loading) {
    state.loading = loading;

    var button = el('st-refresh');

    if (!button) {
      return;
    }

    button.disabled = loading;
    button.innerHTML = loading
      ? '<i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 正在读取'
      : '<i class="layui-icon layui-icon-refresh"></i> 刷新发布包';
  }

  function notify(message) {
    if (state.layer && typeof state.layer.msg === 'function') {
      state.layer.msg(message, { icon: 2, time: 3000 });
      return;
    }

    window.alert(message);
  }

  function fail(message) {
    text('st-summary', message);
    text('st-publish', message);

    var node = el('st-package-list');

    if (node && !node.querySelector('.st-package-card')) {
      node.innerHTML = '<div class="st-package-empty"></div>';
      node.firstChild.textContent = message;
    }

    notify(message);
  }

  function packageCard(row) {
    var version = row.version
      ? '<span class="st-package-version">' + escapeHtml(row.version) + '</span>'
      : '';
    var description = row.description
      ? '<p class="st-package-desc">' + escapeHtml(row.description) + '</p>'
      : '<p class="st-package-desc is-empty">暂无描述</p>';
    var href = safeUrl(row.home);
    var home = href
      ? '<a class="st-home-link" href="' + escapeHtml(href) + '" target="_blank" rel="noopener">' + escapeHtml(href) + '</a>'
      : '<span class="st-package-muted">未配置首页</span>';
    var path = row.path
      ? '<code class="st-path">' + escapeHtml(row.path) + '</code>'
      : '<span class="st-package-muted">未找到安装目录</span>';

    return '<article class="st-package-card">'
      + '<div class="st-package-head">'
      + '<span class="st-package-icon"><i class="layui-icon layui-icon-component"></i></span>'
      + '<span class="st-package-name">' + escapeHtml(row.package) + '</span>'
      + version
      + '</div>'
      + description
      + '<div class="st-package-meta">'
      + '<span class="st-package-label"><i class="layui-icon layui-icon-website"></i>首页</span>'
      + home
      + '<span class="st-package-label"><i class="layui-icon layui-icon-file"></i>安装目录</span>'
      + path
      + '</div>'
      + '</article>';
  }

  function renderPackages(list) {
    var node = el('st-package-list');

    if (!node) {
      return;
    }

    if (!list.length) {
      node.innerHTML = '<div class="st-package-empty">暂无已安装的 think-store 发布包</div>';
      return;
    }

    node.innerHTML = list.map(packageCard).join('');
  }

  function render(body) {
    var list = body && Array.isArray(body.packages) ? body.packages : [];
    var total = body && typeof body.total === 'number' ? body.total : list.length;

    state.data = body;

    text('st-total', total);
    text('st-total-metric', total);
    text('st-summary', '共 ' + total + ' 个发布包');
    text('st-json-count', total);
    text('st-publish', '已读取 ' + total + ' 个发布包 · ' + stamp());
    text('st-hero-hint', total > 0 ? '按 prefix 识别 · 只读' : '暂未发现匹配的发布包');

    var json = el('st-json');

    if (json) {
      json.textContent = JSON.stringify(body, null, 2);
    }

    renderPackages(list);
  }

  function load() {
    if (state.loading) {
      return;
    }

    setLoading(true);
    text('st-summary', '正在读取发布包…');

    var node = el('st-package-list');

    if (node && !node.querySelector('.st-package-card')) {
      node.innerHTML = '<div class="st-package-empty">正在读取发布包…</div>';
    }

    fetch(api + '/think-store/packages', {
      headers: { Accept: 'application/json' },
      cache: 'no-store'
    })
      .then(function (response) {
        return response.json().catch(function () {
          return {};
        }).then(function (body) {
          if (!response.ok) {
            throw new Error((body && body.message) || '请求失败，HTTP ' + response.status);
          }

          return body;
        });
      })
      .then(render)
      .catch(function (error) {
        fail(error && error.message ? error.message : '接口请求失败');
      })
      .then(function () {
        setLoading(false);
      });
  }

  function initJson() {
    var toggle = el('st-json-toggle');
    var wrap = el('st-json-wrap');

    if (!toggle || !wrap) {
      return;
    }

    toggle.addEventListener('click', function () {
      var opened = wrap.classList.toggle('is-open');

      toggle.innerHTML = opened
        ? '<i class="layui-icon layui-icon-up"></i> 收起'
        : '<i class="layui-icon layui-icon-down"></i> 展开';
    });
  }

  function initNav() {
    var toggle = el('st-nav-toggle');
    var nav = el('st-nav');

    if (!toggle || !nav) {
      return;
    }

    var icon = toggle.querySelector('i');

    function close() {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', '打开导航');

      if (icon) {
        icon.className = 'layui-icon layui-icon-spread-left';
      }
    }

    function open() {
      nav.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', '关闭导航');

      if (icon) {
        icon.className = 'layui-icon layui-icon-close';
      }
    }

    toggle.addEventListener('click', function (event) {
      event.stopPropagation();

      if (nav.classList.contains('is-open')) {
        close();
      } else {
        open();
      }
    });

    nav.addEventListener('click', function (event) {
      if (event.target instanceof Element && event.target.closest('a')) {
        close();
      }
    });

    document.addEventListener('click', function (event) {
      if (!nav.classList.contains('is-open')) {
        return;
      }

      if (nav.contains(event.target) || toggle.contains(event.target)) {
        return;
      }

      close();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        close();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 820) {
        close();
      }
    });
  }

  function initAnchorNav() {
    var links = document.querySelectorAll('.st-nav a');

    links.forEach(function (link) {
      link.addEventListener('click', function () {
        if (this.getAttribute('href').charAt(0) !== '#') {
          return;
        }

        links.forEach(function (item) {
          item.classList.remove('is-active');
        });

        this.classList.add('is-active');
      });
    });
  }

  function boot() {
    initNav();
    initJson();
    initAnchorNav();

    if (!el('st-package-list')) {
      return;
    }

    state.layer = window.layui.layer;

    var refresh = el('st-refresh');

    if (refresh) {
      refresh.addEventListener('click', load);
    }

    load();
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (window.layui && typeof window.layui.use === 'function') {
      window.layui.use(['layer'], boot);
      return;
    }

    boot();
  });
})();