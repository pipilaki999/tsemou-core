const risingStories = [
  {
    rank: 1,
    title: "Lead-tainted river corridor expands to 46 villages",
    metric: "1.9k interactions",
    score: 97,
    rising: "+12 today",
    image: "https://picsum.photos/seed/tsemou-rising-1/120/90"
  },
  {
    rank: 2,
    title: "Dormitory fire permits linked to exemption chain",
    metric: "1.3k interactions",
    score: 91,
    rising: "Rising Fast",
    image: "https://picsum.photos/seed/tsemou-rising-2/120/90"
  },
  {
    rank: 3,
    title: "Insulin overpricing network mapped across districts",
    metric: "1.1k interactions",
    score: 86,
    rising: "+8",
    image: "https://picsum.photos/seed/tsemou-rising-3/120/90"
  },
  {
    rank: 4,
    title: "Heatwave mortality undercount disputed by clinics",
    metric: "824 interactions",
    score: 81,
    rising: "+4",
    image: "https://picsum.photos/seed/tsemou-rising-4/120/90"
  },
  {
    rank: 5,
    title: "School meal shell vendors traced to duplicate IDs",
    metric: "693 interactions",
    score: 77,
    rising: "+6",
    image: "https://picsum.photos/seed/tsemou-rising-5/120/90"
  }
];

const companyLeaders = {
  best: [
    { name: "Nordic Grid", metric: "+18 trust" },
    { name: "Helios Transit", metric: "+13 trust" },
    { name: "BlueRiver Water", metric: "+9 trust" }
  ],
  worst: [
    { name: "Arcturon Chem", metric: "-22 trust" },
    { name: "Kharon Textile", metric: "-16 trust" },
    { name: "DeltaMine", metric: "-13 trust" }
  ]
};

const stories = [
  {
    importance: "critical",
    updated: "12m ago",
    title: "Copper tailings leak expands across three farming districts",
    summary: "Independent labs confirmed heavy-metal traces in irrigation channels used by 46 villages. Local clinics report rising skin and gastrointestinal symptoms.",
    evidence: 22,
    peopleAffected: "46 villages across 3 districts",
    status: "Escalating",
    impact: {
      human: 0.9,
      environmental: 0.86,
      economic: 0.67,
      social: 0.72
    },
    caseTimeline: [
      "Initial Report",
      "Evidence Growing",
      "Community Discussion",
      "Company Response",
      "Current Status"
    ],
    whyMatters: [
      "Food and water safety is now uncertain for farming communities.",
      "Children and elderly residents are showing early health signals.",
      "Delays in containment could widen cross-border impact."
    ],
    companies: "Arcturon Chemicals, DeltaMine Logistics",
    countries: "Peru, Bolivia",
    solutions: 7,
    timeline: "Alert -> first sampling -> government notice -> emergency hearing",
    image: "https://picsum.photos/seed/tsemou-story-1/960/540"
  },
  {
    importance: "high",
    updated: "28m ago",
    title: "Migrant dormitory fire reveals undocumented safety exemptions",
    summary: "Inspection records show repeated exemptions granted to labor housing blocks despite blocked exits and missing alarms.",
    evidence: 16,
    companies: "Kharon Textiles Group, Harbor Workforce Management",
    countries: "Malaysia, Indonesia",
    solutions: 4,
    timeline: "Incident -> permit audit -> survivor statements -> legal filing",
    image: "https://picsum.photos/seed/tsemou-story-2/960/540"
  },
  {
    importance: "elevated",
    updated: "1h ago",
    title: "Public hospital procurement records show insulin price inflation",
    summary: "Regional procurement logs indicate repeated overpricing with identical suppliers across neighboring health districts.",
    evidence: 19,
    companies: "Mercia Clinical Labs, Novaline Distributors",
    countries: "Kenya, Uganda",
    solutions: 6,
    timeline: "Whistle report -> invoice match -> supplier network map",
    image: "https://picsum.photos/seed/tsemou-story-3/960/540"
  },
  {
    importance: "high",
    updated: "1h ago",
    title: "Drought relocation plan excludes informal settlements from water access",
    summary: "Municipal drought maps route emergency tankers away from communities missing official land registration.",
    evidence: 13,
    companies: "Civic Water Bureau, Southbay Port Holdings",
    countries: "Morocco",
    solutions: 5,
    timeline: "Policy release -> community mapping -> emergency petition",
    image: "https://picsum.photos/seed/tsemou-story-4/960/540"
  },
  {
    importance: "critical",
    updated: "2h ago",
    title: "Battery recycling yards linked to child lead exposure cluster",
    summary: "Pediatric units in two provinces report abnormal lead levels near informal recycling corridors lacking filtration standards.",
    evidence: 25,
    companies: "Radian Battery Recoveries, BlueRiver Waste",
    countries: "India, Bangladesh",
    solutions: 8,
    timeline: "Clinic data -> yard mapping -> lab confirmation -> closure requests",
    image: "https://picsum.photos/seed/tsemou-story-5/960/540"
  },
  {
    importance: "elevated",
    updated: "2h ago",
    title: "Gig delivery algorithm penalties reduce rider income below legal floor",
    summary: "Riders report sudden account penalties tied to opaque route scoring, cutting weekly earnings by up to 34 percent.",
    evidence: 11,
    companies: "SwiftDrop, MetroCart",
    countries: "Brazil, Colombia",
    solutions: 3,
    timeline: "Forum reports -> pay-slip analysis -> labor board intake",
    image: "https://picsum.photos/seed/tsemou-story-6/960/540"
  },
  {
    importance: "high",
    updated: "3h ago",
    title: "School meal contracts routed through shell vendors with duplicate addresses",
    summary: "Parent groups uncovered overlap between approved vendors and dormant companies sharing tax IDs and banking routes.",
    evidence: 18,
    companies: "Granary Foods Consortium",
    countries: "Philippines",
    solutions: 4,
    timeline: "Parent audit -> registry match -> municipal inquiry",
    image: "https://picsum.photos/seed/tsemou-story-7/960/540"
  },
  {
    importance: "elevated",
    updated: "3h ago",
    title: "Heatwave mortality map omits undocumented workers in official count",
    summary: "Community morgue registries suggest undercounting in heat-related deaths where workers lacked formal residency documents.",
    evidence: 12,
    companies: "UrbanWorks Contractors",
    countries: "Spain",
    solutions: 5,
    timeline: "Mortality map release -> registry compare -> correction demand",
    image: "https://picsum.photos/seed/tsemou-story-8/960/540"
  },
  {
    importance: "high",
    updated: "4h ago",
    title: "Port expansion noise exemptions approved without resident consultation",
    summary: "Approval documents show nighttime operating waivers issued before required neighborhood consultation sessions occurred.",
    evidence: 14,
    companies: "Southbay Port Holdings",
    countries: "Ghana",
    solutions: 2,
    timeline: "Permit release -> public hearing gap -> legal challenge",
    image: "https://picsum.photos/seed/tsemou-story-9/960/540"
  },
  {
    importance: "elevated",
    updated: "5h ago",
    title: "Community clinics report vaccine cold-chain failures after grid outages",
    summary: "Facility logs from six districts reveal repeated refrigeration breaks during outages, affecting immunization quality control.",
    evidence: 10,
    companies: "Nordic Grid Renewables",
    countries: "Nigeria",
    solutions: 3,
    timeline: "Outage logs -> clinic interviews -> equipment audit",
    image: "https://picsum.photos/seed/tsemou-story-10/960/540"
  },
  {
    importance: "high",
    updated: "6h ago",
    title: "Municipal landfill smoke linked to asthma spike in nearby schools",
    summary: "Air-quality volunteers tracked particulate peaks during unannounced burn cycles near three primary school zones.",
    evidence: 17,
    companies: "GreenCore Municipal Waste",
    countries: "Mexico",
    solutions: 6,
    timeline: "Sensor deployment -> pediatric alerts -> closure request",
    image: "https://picsum.photos/seed/tsemou-story-11/960/540"
  },
  {
    importance: "elevated",
    updated: "7h ago",
    title: "Women fish vendors excluded from new harbor permit registry",
    summary: "Updated digital permit flow requires identity documentation many informal women-led cooperatives do not possess.",
    evidence: 9,
    companies: "Harbor Commerce Authority",
    countries: "Senegal",
    solutions: 4,
    timeline: "Registry launch -> exclusion reports -> appeals filing",
    image: "https://picsum.photos/seed/tsemou-story-12/960/540"
  }
];

const newsItems = [
  {
    type: "POST",
    title: "Night-shift nurses log expired cooling units in ward C",
    author: "Mila Santos",
    time: "2m ago",
    image: "https://picsum.photos/seed/tsemou-feed-1/120/120"
  },
  {
    type: "EVIDENCE",
    title: "Lab certificate uploaded for river toxicity sample #77",
    author: "Amadou Keita",
    time: "5m ago",
    image: "https://picsum.photos/seed/tsemou-feed-2/120/120"
  },
  {
    type: "STORY",
    title: "Parents report duplicate school meal vendors in district 11",
    author: "Rina Delos Reyes",
    time: "9m ago",
    image: "https://picsum.photos/seed/tsemou-feed-3/120/120"
  },
  {
    type: "SOLUTION",
    title: "Community proposal: open procurement ledger by neighborhood",
    author: "Adewale K.",
    time: "14m ago",
    image: "https://picsum.photos/seed/tsemou-feed-4/120/120"
  },
  {
    type: "QUESTION",
    title: "Why are tanker deliveries skipping informal settlements?",
    author: "Lamia Idrissi",
    time: "20m ago",
    image: "https://picsum.photos/seed/tsemou-feed-5/120/120"
  },
  {
    type: "EVIDENCE",
    title: "Audio testimony added from dockworker permit hearing",
    author: "Noah B.",
    time: "27m ago",
    image: "https://picsum.photos/seed/tsemou-feed-6/120/120"
  },
  {
    type: "POST",
    title: "Volunteer map links asthma peaks to landfill burn windows",
    author: "Clara Mendez",
    time: "31m ago",
    image: "https://picsum.photos/seed/tsemou-feed-7/120/120"
  }
];

const communityContainer = document.getElementById("communitySections");
const storyFeed = document.getElementById("storyFeed");
const liveNews = document.getElementById("liveNews");
const heroSearch = document.getElementById("heroSearch");

function renderCommunity() {
  const rankTone = (rank) => {
    if (rank === 1) return "gold";
    if (rank === 2) return "silver";
    if (rank === 3) return "bronze";
    return "";
  };

  communityContainer.innerHTML = `
    <section class="community-block rising" aria-label="Rising Stories">
      <h3>🔥 Rising Stories</h3>
      <ul class="rising-list">
        ${risingStories
          .map(
            (item) => `
            <li class="rising-item ${rankTone(item.rank)}">
              <span class="rank-pill">${item.rank}</span>
              <img src="${item.image}" alt="${item.title}">
              <div>
                <p class="rising-item-title">${item.title}</p>
                <div class="rising-meta">
                  <span>${item.metric}</span>
                  <span class="rising-score">${item.score} <span class="rising-arrow">↑</span></span>
                </div>
                <p class="rising-indicator">▲ ${item.rising}</p>
              </div>
            </li>
          `
          )
          .join("")}
      </ul>
    </section>

    <section class="company-pair" aria-label="Company rankings">
      <article class="company-mini" aria-label="Best Companies">
        <h3>Best Companies</h3>
        <ul>
          ${companyLeaders.best
            .map(
              (item) => `
              <li>
                <strong>${item.name}</strong>
                <span>${item.metric}</span>
              </li>
            `
            )
            .join("")}
        </ul>
      </article>

      <article class="company-mini" aria-label="Worst Companies">
        <h3>Worst Companies</h3>
        <ul>
          ${companyLeaders.worst
            .map(
              (item) => `
              <li>
                <strong>${item.name}</strong>
                <span>${item.metric}</span>
              </li>
            `
            )
            .join("")}
        </ul>
      </article>
    </section>
  `;
}

function storyTemplate(story) {
  return `
    <article class="story-card" tabindex="0">
      <div class="story-body">
        <div class="story-eyebrow">
          <span class="live-story-badge">Live Story</span>
          <span class="story-updated">Updated ${story.updated}</span>
        </div>
        <div class="story-topline">
          <span class="importance ${story.importance}">${story.importance}</span>
        </div>
        <h3 class="story-title">${story.title}</h3>
        <p class="story-summary">${story.summary}</p>
        <div class="story-meta">
          <div class="meta-row"><span class="meta-label">Evidence</span><span>${story.evidence} verified items</span></div>
          <div class="meta-row"><span class="meta-label">Companies</span><span>${story.companies}</span></div>
          <div class="meta-row"><span class="meta-label">Countries</span><span>${story.countries}</span></div>
          <div class="meta-row"><span class="meta-label">Solutions</span><span>${story.solutions} active proposals</span></div>
        </div>
        <p class="timeline">Timeline: ${story.timeline}</p>
        <button class="tsemit-btn" type="button" aria-label="Open TSEMIT actions for ${story.title}">TSEMIT</button>
      </div>
    </article>
  `;
}

function featuredStoryTemplate(story) {
  return `
    <article class="story-card featured-story" tabindex="0">
      <div class="story-body">
        <p class="featured-kicker">TODAY MATTERS</p>
        <div class="story-eyebrow">
          <span class="live-story-badge">Living Case</span>
          <span class="story-updated">Updated ${story.updated}</span>
        </div>
        <div class="story-topline">
          <span class="importance ${story.importance}">${story.importance}</span>
        </div>
        <h3 class="story-title">${story.title}</h3>
        <p class="story-summary">${story.summary}</p>

        <section class="featured-dashboard" aria-label="Story dashboard">
          <div class="dashboard-chip evidence-chip"><span class="chip-icon">E</span><span>Evidence: ${story.evidence}</span></div>
          <div class="dashboard-chip"><span class="chip-icon">P</span><span>People affected: ${story.peopleAffected || "Unknown"}</span></div>
          <div class="dashboard-chip"><span class="chip-icon">C</span><span>Companies: ${story.companies}</span></div>
          <div class="dashboard-chip"><span class="chip-icon">S</span><span>Solutions: ${story.solutions}</span></div>
          <div class="dashboard-chip"><span class="chip-icon">U</span><span>Last update: ${story.updated}</span></div>
        </section>

        <p class="featured-secondary">Countries: ${story.countries}</p>
        <p class="featured-secondary">Case opened: ${story.updated}</p>

        <section class="story-status" aria-label="Story status">
          <h4>Case Status</h4>
          <p class="status-pill">${story.status || "Developing"}</p>
        </section>

        <section class="case-timeline" aria-label="Case timeline">
          <h4>Case Timeline</h4>
          <ul>
            ${(story.caseTimeline || []).map((step, index, arr) => `
              <li class="${index === arr.length - 1 ? "current" : ""}">
                <span class="timeline-dot" aria-hidden="true"></span>
                <span>${step}</span>
              </li>
            `).join("")}
          </ul>
        </section>

        <section class="story-impact" aria-label="Impact overview">
          <h4>Impact</h4>
          <div class="impact-row"><span>Human Impact</span><div class="impact-track"><span style="width:${Math.round((story.impact?.human || 0.6) * 100)}%"></span></div></div>
          <div class="impact-row"><span>Environmental Impact</span><div class="impact-track"><span style="width:${Math.round((story.impact?.environmental || 0.6) * 100)}%"></span></div></div>
          <div class="impact-row"><span>Economic Impact</span><div class="impact-track"><span style="width:${Math.round((story.impact?.economic || 0.6) * 100)}%"></span></div></div>
          <div class="impact-row"><span>Social Impact</span><div class="impact-track"><span style="width:${Math.round((story.impact?.social || 0.6) * 100)}%"></span></div></div>
        </section>

        <section class="why-matters" aria-label="Why this matters">
          <h4>WHY THIS MATTERS</h4>
          ${(story.whyMatters || []).slice(0, 2).map((line) => `<p>${line}</p>`).join("")}
        </section>

        <section class="participation-panel" aria-label="Participation actions">
          <button class="tsemit-btn" type="button" aria-label="Open TSEMIT actions for ${story.title}">TSEMIT</button>
          <div class="participation-links">
            <a href="#" role="button">Add evidence</a>
            <a href="#" role="button">Suggest solution</a>
            <a href="#" role="button">Join discussion</a>
          </div>
        </section>
      </div>
    </article>
  `;
}

function newsTemplate(item) {
  const typeClass = `type-${item.type.toLowerCase()}`;
  return `
    <article class="news-item community-entry" tabindex="0">
      <div>
        <div class="entry-head">
          <span class="entry-type ${typeClass}">${item.type}</span>
        </div>
        <h4 class="entry-title">${item.title}</h4>
        <p class="entry-author">${item.author} • ${item.time}</p>
        <img class="entry-thumb" src="${item.image}" alt="${item.type} thumbnail">
        <div class="entry-actions">
          <button class="vote-btn tsemit-action" type="button" aria-label="TSEMIT this ${item.type.toLowerCase()}">TSEMIT</button>
          <button class="vote-btn untsemit-action" type="button" aria-label="UNTSEMIT this ${item.type.toLowerCase()}">UNTSEMIT</button>
        </div>
      </div>
    </article>
  `;
}

function renderStories(filter = "") {
  const needle = filter.trim().toLowerCase();
  const filtered = stories.filter((story) => {
    if (!needle) return true;
    return (
      story.title.toLowerCase().includes(needle) ||
      story.summary.toLowerCase().includes(needle) ||
      story.companies.toLowerCase().includes(needle) ||
      story.countries.toLowerCase().includes(needle)
    );
  });

  storyFeed.innerHTML = filtered
    .map((story, index) => (index === 0 ? featuredStoryTemplate(story) : storyTemplate(story)))
    .join("");
}

function renderNews(filter = "") {
  const needle = filter.trim().toLowerCase();
  const filtered = newsItems.filter((item) => {
    if (!needle) return true;
    return (
      item.type.toLowerCase().includes(needle) ||
      item.title.toLowerCase().includes(needle) ||
      item.author.toLowerCase().includes(needle)
    );
  });

  liveNews.innerHTML = filtered.map(newsTemplate).join("");
}

if (heroSearch) {
  heroSearch.addEventListener("input", () => {
    renderStories(heroSearch.value);
    renderNews(heroSearch.value);
  });
}

const chips = Array.from(document.querySelectorAll(".topic-chip"));
chips.forEach((chip) => {
  chip.addEventListener("click", () => {
    chips.forEach((c) => c.classList.remove("active"));
    chip.classList.add("active");
  });
});

renderCommunity();
renderStories();
renderNews();
