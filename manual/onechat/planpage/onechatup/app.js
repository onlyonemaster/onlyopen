
(function () {
  const tbody = document.getElementById('comparisonBody');
  const tabs = document.getElementById('sectionTabs');
  const searchInput = document.getElementById('searchInput');
  const priorityFilter = document.getElementById('priorityFilter');
  const onechatFilter = document.getElementById('onechatFilter');
  const resultCount = document.getElementById('resultCount');
  const currentSectionLabel = document.getElementById('currentSectionLabel');
  const summaryCards = document.getElementById('summaryCards');

  const sections = ['all'].concat(window.comparisonMeta.sections);
  let currentSection = 'all';

  function renderSummary() {
    const total = window.comparisonData.length;
    const must = window.comparisonData.filter(x => x.priority === '필수').length;
    const diff = window.comparisonData.filter(x => x.priority === '차별').length;
    const gaps = window.comparisonData.filter(x => x.onechat === '갭').length;
    const cards = [
      { label: '총 기능 수', value: total + '개', desc: '데이터 파일로 분리된 전체 비교 기능 수' },
      { label: '필수 우선순위', value: must + '개', desc: '원챗이 먼저 구현해야 하는 핵심 운영 기능' },
      { label: '차별 우선순위', value: diff + '개', desc: '원챗의 강점을 더 크게 만드는 확장 기능' },
      { label: '원챗 갭 항목', value: gaps + '개', desc: '현재 보강이 필요한 주요 공백 기능 수' }
    ];
    summaryCards.innerHTML = cards.map(card => '<div class="summary-card"><div class="label">'+card.label+'</div><strong>'+card.value+'</strong><p>'+card.desc+'</p></div>').join('');
  }

  function renderTabs() {
    tabs.innerHTML = sections.map(section => {
      const label = section === 'all' ? '전체 보기' : section;
      return '<button class="tab-btn ' + (section === currentSection ? 'active' : '') + '" data-section="' + section + '">' + label + '</button>';
    }).join('');
    Array.prototype.forEach.call(tabs.querySelectorAll('.tab-btn'), function(btn) {
      btn.addEventListener('click', function() {
        currentSection = btn.getAttribute('data-section');
        renderTabs();
        renderTable();
      });
    });
  }

  function matches(record, query) {
    if (!query) return true;
    const haystack = [
      String(record.id), record.section, record.feature, record.salesforce, record.zendesk,
      record.intercom, record.onechat, record.priority, record.suggestion
    ].join(' ').toLowerCase();
    return haystack.indexOf(query.toLowerCase()) !== -1;
  }

  function filterData() {
    const query = searchInput.value.trim();
    const pr = priorityFilter.value;
    const oc = onechatFilter.value;
    return window.comparisonData.filter(function(record) {
      const okSection = currentSection === 'all' ? true : record.section === currentSection;
      const okPriority = pr === 'all' ? true : record.priority === pr;
      const okOnechat = oc === 'all' ? true : record.onechat === oc;
      const okSearch = matches(record, query);
      return okSection && okPriority && okOnechat && okSearch;
    });
  }

  function renderTable() {
    const data = filterData();
    resultCount.textContent = '총 ' + data.length + '개';
    currentSectionLabel.textContent = currentSection === 'all' ? '전체 보기' : currentSection;

    tbody.innerHTML = data.map(function(row) {
      return '<tr>' +
        '<td>' + row.id + '. ' + row.feature + '</td>' +
        '<td><span class="mark m-' + row.salesforce + '">' + row.salesforce + '</span></td>' +
        '<td><span class="mark m-' + row.zendesk + '">' + row.zendesk + '</span></td>' +
        '<td><span class="mark m-' + row.intercom + '">' + row.intercom + '</span></td>' +
        '<td class="td-onechat"><span class="mark m-' + row.onechat + '">' + row.onechat + '</span></td>' +
        '<td class="td-ai"><span class="tag p-' + row.priority + '">' + row.priority + '</span>' + row.suggestion + '</td>' +
      '</tr>';
    }).join('');
  }

  searchInput.addEventListener('input', renderTable);
  priorityFilter.addEventListener('change', renderTable);
  onechatFilter.addEventListener('change', renderTable);

  renderSummary();
  renderTabs();
  renderTable();
})();
