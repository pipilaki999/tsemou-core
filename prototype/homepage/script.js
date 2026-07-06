const communityData = [
  {
    title: "Best Companies",
    items: [
      { name: "Nordic Grid Renewables", metric: "+18 trust" },
      { name: "Helios Public Transit", metric: "+13 trust" },
      { name: "BlueRiver Water Systems", metric: "+9 trust" },
      { name: "Mercia Clinical Labs", metric: "+7 trust" }
    ]
  },
  {
    title: "Worst Companies",
    items: [
      { name: "Arcturon Chemicals", metric: "-22 trust" },
      { name: "Kharon Textiles Group", metric: "-16 trust" },
      { name: "DeltaMine Logistics", metric: "-13 trust" },
      { name: "Southbay Port Holdings", metric: "-10 trust" }
    ]
  },
  {
    title: "Best Public Figures",
    items: [
      { name: "Dr. Elena Morais", metric: "4 verified actions" },
      { name: "Jamal Adeyemi", metric: "3 solutions shipped" },
      { name: "Sofia Karim", metric: "2 policy updates" }
    ]
  },
  {
    title: "Worst Public Figures",
    items: [
      { name: "Victor Hale", metric: "7 unresolved claims" },
      { name: "Mara Dexton", metric: "5 evidence conflicts" },
      { name: "Anton Reyes", metric: "4 unresolved updates" }
    ]
  },
  {
    title: "Community Discoveries",
    items: [
      { name: "Shadow subcontractor trail", metric: "New in Manila" },
      { name: "Unreported waste route", metric: "New in Lagos" },
      { name: "School meal contract leak", metric: "New in Lima" }
    ]
  },
  {
    title: "Most Wanted Solutions",
    items: [
      { name: "Open supply chain registry", metric: "2.4k support" },
      { name: "City heat shelter map", metric: "1.7k support" },
      { name: "Public drug pricing board", metric: "1.2k support" }
    ]
  },
  {
    title: "Active TSEMITs",
    items: [
      { name: "Night shift testimonies", metric: "129 active" },
      { name: "River toxicity samples", metric: "103 active" },
      { name: "Transit access audit", metric: "84 active" }
    ]
  }
];

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
  ["21:54", "Reuters", "Labor", "medium", "Dockworker unions in Valencia confirm talks resume after midnight mediation"],
  ["21:47", "AP", "Climate", "low", "Flood barriers opened in two Dutch towns after river pressure warning"],
  ["21:40", "BBC", "Health", "medium", "Regional health agency issues mosquito-borne illness advisory"],
  ["21:34", "Al Jazeera", "Energy", "high", "Power cuts continue in central districts after transformer fire"],
  ["21:26", "Nikkei", "Economy", "low", "Rice export limits eased as domestic stockpiles recover"],
  ["21:18", "AFP", "Justice", "medium", "Court grants interim relief in municipal housing displacement case"],
  ["21:10", "Guardian", "Rights", "high", "Rights monitors publish detention access gaps at border sites"],
  ["21:03", "DW", "Transport", "low", "Night rail service restored on Berlin regional corridor"],
  ["20:56", "Reuters", "Industry", "medium", "Battery supplier recalls two lots after safety inspection"],
  ["20:49", "AP", "Education", "low", "Public schools extend meal support through summer break"],
  ["20:43", "BBC", "Environment", "medium", "Coastal erosion maps updated for Atlantic settlements"],
  ["20:36", "Financial Times", "Energy", "high", "Gas storage reserve falls below seasonal benchmark"],
  ["20:29", "Le Monde", "Politics", "low", "Coalition parties reopen negotiation on anti-corruption bill"],
  ["20:22", "Reuters", "Agriculture", "medium", "Fertilizer shipments delayed at Pacific freight terminals"],
  ["20:16", "AP", "Health", "high", "Children's ward in capital city reaches capacity amid heatwave"],
  ["20:09", "Bloomberg", "Finance", "low", "Sovereign bond spread narrows after policy statement"],
  ["20:01", "BBC", "Labor", "medium", "Factory shift records reviewed after overtime complaints"],
  ["19:54", "El Pais", "Water", "high", "Reservoir level drops trigger emergency usage restrictions"],
  ["19:47", "Reuters", "Digital", "low", "National telecom outage resolved after routing update"],
  ["19:40", "AP", "Justice", "medium", "Prosecutors file procurement bid-rigging case in port city"],
  ["19:34", "NPR", "Housing", "low", "Tenants' association opens legal clinic in three neighborhoods"],
  ["19:27", "Reuters", "Climate", "medium", "Wildfire line expands near mountain highway"],
  ["19:20", "BBC", "Health", "low", "Public dashboard adds vaccination inventory transparency tab"],
  ["19:12", "AFP", "Conflict", "high", "Ceasefire monitoring mission reports overnight violations"],
  ["19:05", "Wall Street Journal", "Economy", "medium", "Port congestion eases as customs backlog clears"],
  ["18:59", "Reuters", "Supply Chain", "medium", "Textile mill shutdown affects four export hubs"],
  ["18:52", "AP", "Technology", "low", "Open-data portal adds procurement machine-readable release"],
  ["18:44", "BBC", "Rights", "medium", "Civil coalition calls for migrant shelter oversight panel"],
  ["18:37", "Reuters", "Energy", "high", "Grid operator issues evening peak demand emergency notice"],
  ["18:30", "Guardian", "Environment", "low", "Urban tree canopy pilot launches in three districts"],
  ["18:23", "AP", "Food", "medium", "School pantry network reports rising weekend demand"],
  ["18:15", "Reuters", "Justice", "high", "Audit shows contractor blacklist not applied in 11 tenders"],
  ["18:08", "BBC", "Transport", "low", "Metro station accessibility lift repairs completed"],
  ["18:01", "Reuters", "Labor", "medium", "Rider cooperatives demand transparent dispatch scoring"],
  ["17:54", "DW", "Health", "low", "Community clinic staffing stabilizes after emergency hires"],
  ["17:47", "Reuters", "Water", "medium", "Pipeline contamination alert prompts district-level boil notice"],
  ["17:39", "AP", "Politics", "low", "City council schedules open hearing on industrial zoning"],
  ["17:33", "BBC", "Climate", "high", "Heat index exceeds record level across southern corridor"],
  ["17:26", "Reuters", "Education", "medium", "Parents request independent review of school vendor registry"],
  ["17:19", "AFP", "Health", "medium", "Local labs publish updated particulate exposure readings"]
];

const communityContainer = document.getElementById("communitySections");
const storyFeed = document.getElementById("storyFeed");
const liveNews = document.getElementById("liveNews");
const heroSearch = document.getElementById("heroSearch");

function renderCommunity() {
  communityContainer.innerHTML = communityData
    .map(
      (section) => `
      <section class="community-block" aria-label="${section.title}">
        <h3>${section.title}</h3>
        <ul>
          ${section.items
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
      </section>
    `
    )
    .join("");
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
  const impactClass = item[3] === "high" ? "impact-high" : item[3] === "medium" ? "impact-medium" : "impact-low";
  return `
    <article class="news-item" tabindex="0">
      <div class="news-top">
        <span>${item[0]}</span>
        <span>${item[1]}</span>
      </div>
      <h4 class="news-headline">${item[4]}</h4>
      <div class="news-bottom">
        <span>${item[2]}</span>
        <span><span class="impact-dot ${impactClass}" aria-hidden="true"></span>${item[3]} impact</span>
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
      item[1].toLowerCase().includes(needle) ||
      item[2].toLowerCase().includes(needle) ||
      item[4].toLowerCase().includes(needle)
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
