(function () {
  'use strict';

  var cfg = window.STORE_CONFIG || {};
  var api = String(cfg.api || '').replace(/\/+$/, '');
  var state = {
    table: null,
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
    notify(message);
  }

  function renderTable(list) {
    if (!state.table) {
      return;
    }

    state.table.render({
      elem: '#st-package-table',
      id: 'st-package-table',
      data: list,
      page: false,
      even: true,
      text: {
        none: '暂无已安装的 think-store 发布包'
      },
      cols: [[
        {
          field: 'package',
          title: '发布包',
          minWidth: 280,
          templet: function (row) {
            var name = '<span class="st-package-name">' + escapeHtml(row.package) + '</span>';
            var description = row.description
              ? '<span class="st-package-desc">' + escapeHtml(row.description) + '</span>'
              : '';

            return name + description;
          }
        },
        {
          field: 'version',
          title: '版本',
          width: 110,
          align: 'center',
          templet: function (row) {
            return row.version
              ? '<code class="st-path">' + escapeHtml(row.version) + '</code>'
              : '<span class="st-muted-text">—</span>';
          }
        },
        {
          field: 'path',
          title: '安装目录',
          minWidth: 300,
          templet: function (row) {
            return row.path
              ? '<code class="st-path">' + escapeHtml(row.path) + '</code>'
              : '<span class="st-muted-text">—</span>';
          }
        }
      ]]
    });
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

    renderTable(list);
  }

  function load() {
    if (state.loading) {
      return;
    }

    setLoading(true);
    text('st-summary', '正在读取发布包…');

    fetch(api + '/packages', {
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

    if (!el('st-package-table')) {
      return;
    }

    state.table = window.layui.table;
    state.layer = window.layui.layer;

    var refresh = el('st-refresh');

    if (refresh) {
      refresh.addEventListener('click', load);
    }

    load();
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (window.layui && typeof window.layui.use === 'function') {
      window.layui.use(['table', 'layer'], boot);
      return;
    }

    boot();
  });
})();