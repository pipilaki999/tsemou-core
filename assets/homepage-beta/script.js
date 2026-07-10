const defaultRisingStories = [
  {
    rank: 1,
    title: "Lead-tainted river corridor expands to 46 villages",
    metric: "1.9k interactions",
    score: 97,
    rising: "+12 today",
    image: ""
  },
  {
    rank: 2,
    title: "Dormitory fire permits linked to exemption chain",
    metric: "1.3k interactions",
    score: 91,
    rising: "Rising Fast",
    image: ""
  },
  {
    rank: 3,
    title: "Insulin overpricing network mapped across districts",
    metric: "1.1k interactions",
    score: 86,
    rising: "+8",
    image: ""
  },
  {
    rank: 4,
    title: "Heatwave mortality undercount disputed by clinics",
    metric: "824 interactions",
    score: 81,
    rising: "+4",
    image: ""
  },
  {
    rank: 5,
    title: "School meal shell vendors traced to duplicate IDs",
    metric: "693 interactions",
    score: 77,
    rising: "+6",
    image: ""
  }
];

const defaultCompanyLeaders = {
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

let risingStories = [...defaultRisingStories];
let companyLeaders = JSON.parse(JSON.stringify(defaultCompanyLeaders));

const defaultStories = [
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
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
    image: ""
  }
];

let stories = [...defaultStories];
const injectedFeedPosts = Array.isArray(window.__TSEMOU_BETA_FEED_POSTS__) ? window.__TSEMOU_BETA_FEED_POSTS__ : [];

const defaultNewsItems = [
  {
    type: "POST",
    title: "Night-shift nurses log expired cooling units in ward C",
    author: "Mila Santos",
    time: "2m ago",
    image: ""
  },
  {
    type: "EVIDENCE",
    title: "Lab certificate uploaded for river toxicity sample #77",
    author: "Amadou Keita",
    time: "5m ago",
    image: ""
  },
  {
    type: "STORY",
    title: "Parents report duplicate school meal vendors in district 11",
    author: "Rina Delos Reyes",
    time: "9m ago",
    image: ""
  },
  {
    type: "SOLUTION",
    title: "Community proposal: open procurement ledger by neighborhood",
    author: "Adewale K.",
    time: "14m ago",
    image: ""
  },
  {
    type: "QUESTION",
    title: "Why are tanker deliveries skipping informal settlements?",
    author: "Lamia Idrissi",
    time: "20m ago",
    image: ""
  },
  {
    type: "EVIDENCE",
    title: "Audio testimony added from dockworker permit hearing",
    author: "Noah B.",
    time: "27m ago",
    image: ""
  },
  {
    type: "POST",
    title: "Volunteer map links asthma peaks to landfill burn windows",
    author: "Clara Mendez",
    time: "31m ago",
    image: ""
  }
];

let newsItems = [...defaultNewsItems];

const communityContainer = document.getElementById("communitySections");
const storyFeed = document.getElementById("storyFeed");
const liveNews = document.getElementById("liveNews");
const heroSearch = document.getElementById("heroSearch");
let communityActionUrl = "#";

function detectWpApiBase() {
  const origin = window.location.origin;
  if (!origin || origin === "null") return null;
  return `${origin}/wp-json/wp/v2`;
}

const wpApiBase = detectWpApiBase();

async function fetchWpCollection(endpoint, query = {}) {
  if (!wpApiBase) return [];

  const params = new URLSearchParams(query);
  const url = `${wpApiBase}/${endpoint}${params.toString() ? `?${params.toString()}` : ""}`;

  try {
    const response = await fetch(url, { headers: { Accept: "application/json" } });
    if (!response.ok) return [];

    const data = await response.json();
    return Array.isArray(data) ? data : [];
  } catch (error) {
    return [];
  }
}

async function fetchWpCollectionTotals(endpoint, query = {}) {
  if (!wpApiBase) return { items: [], total: 0, available: false };

  const params = new URLSearchParams(query);
  const url = `${wpApiBase}/${endpoint}${params.toString() ? `?${params.toString()}` : ""}`;

  try {
    const response = await fetch(url, { headers: { Accept: "application/json" } });
    if (!response.ok) return { items: [], total: 0, available: false };

    const data = await response.json();
    const items = Array.isArray(data) ? data : [];
    const totalHeader = response.headers.get("X-WP-Total");
    const total = Number.parseInt(totalHeader || `${items.length}`, 10);

    return {
      items,
      total: Number.isFinite(total) ? total : items.length,
      available: true
    };
  } catch (error) {
    return { items: [], total: 0, available: false };
  }
}

function decodeHtml(input) {
  const parser = new DOMParser();
  const parsed = parser.parseFromString(input || "", "text/html");
  return (parsed.documentElement.textContent || "").trim();
}

function toShortRelativeTime(dateInput) {
  if (!dateInput) return "recently";

  const now = Date.now();
  const then = new Date(dateInput).getTime();
  if (Number.isNaN(then)) return "recently";

  const deltaMinutes = Math.max(1, Math.floor((now - then) / 60000));
  if (deltaMinutes < 60) return `${deltaMinutes}m ago`;

  const deltaHours = Math.floor(deltaMinutes / 60);
  if (deltaHours < 24) return `${deltaHours}h ago`;

  const deltaDays = Math.floor(deltaHours / 24);
  return `${deltaDays}d ago`;
}

function scoreFromStoryPost(post) {
  const commentCount = Number.parseInt(post?.comment_count || 0, 10);
  const lengthSignal = Math.min(40, Math.floor((post?.content?.rendered || "").length / 120));
  return Math.max(1, Math.min(100, 50 + commentCount * 5 + lengthSignal));
}

function deriveCompanyMentions(storyPosts) {
  const counts = new Map();

  storyPosts.forEach((post) => {
    const content = decodeHtml(post?.content?.rendered || "");
    const matches = content.match(/[A-Z][a-zA-Z0-9&.-]{2,}(?:\s+[A-Z][a-zA-Z0-9&.-]{2,}){0,2}/g) || [];
    matches.slice(0, 30).forEach((name) => {
      const normalized = name.trim();
      if (normalized.length < 4) return;
      counts.set(normalized, (counts.get(normalized) || 0) + 1);
    });
  });

  const ranked = [...counts.entries()]
    .filter(([, count]) => count > 1)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 6);

  return {
    best: ranked.slice(0, 3).map(([name, count]) => ({ name, metric: `${count} mentions` })),
    worst: ranked.slice(3, 6).map(([name, count]) => ({ name, metric: `${count} mentions` }))
  };
}

function normalizeTrustScore(raw) {
  const value = Number.parseFloat(raw);
  if (!Number.isFinite(value)) return null;
  return Math.max(0, Math.min(10, value));
}

function extractTrustScoreFromCompanyPost(post) {
  const directMeta = post?.meta?._tsemou_trust_engine_score;
  const altMeta = post?._tsemou_trust_engine_score;
  const customFields = post?.custom_fields?._tsemou_trust_engine_score;
  const acfField = post?.acf?._tsemou_trust_engine_score || post?.acf?.tsemou_trust_engine_score;
  const contentText = decodeHtml(post?.content?.rendered || "");
  const trustMatch = contentText.match(/trust\s*(?:score)?\s*[:\-]?\s*([0-9]+(?:\.[0-9]+)?)/i);

  return (
    normalizeTrustScore(directMeta) ??
    normalizeTrustScore(altMeta) ??
    normalizeTrustScore(Array.isArray(customFields) ? customFields[0] : customFields) ??
    normalizeTrustScore(acfField) ??
    normalizeTrustScore(trustMatch?.[1])
  );
}

async function loadCommunityRankingsFromBackend() {
  const storyPosts = await fetchWpCollection("story", {
    per_page: 12,
    status: "publish",
    orderby: "modified",
    order: "desc",
    _fields: "id,title,modified,comment_count,content"
  });

  if (storyPosts.length >= 5) {
    risingStories = storyPosts.slice(0, 5).map((post, index) => ({
      rank: index + 1,
      title: decodeHtml(post?.title?.rendered || "Untitled story"),
      metric: `${Number.parseInt(post?.comment_count || 0, 10)} interactions`,
      score: scoreFromStoryPost(post),
      rising: toShortRelativeTime(post?.modified),
      image: ""
    }));

    const companyPosts = await fetchWpCollection("company", {
      per_page: 20,
      status: "publish",
      orderby: "modified",
      order: "desc",
      _fields: "id,title,modified"
    });

    if (companyPosts.length >= 6) {
      const ranked = companyPosts
        .map((post, index) => ({
          name: decodeHtml(post?.title?.rendered || "Unnamed company"),
          trustScore: extractTrustScoreFromCompanyPost(post),
          actionUrl: post?.link ? `${post.link}#tsemou-community-vote` : "#",
          freshness: new Date(post?.modified || 0).getTime() || 0,
          tie: index
        }))
        .map((company) => ({
          ...company,
          metric: company.trustScore !== null
            ? `Trust ${company.trustScore.toFixed(1)}/10`
            : `${toShortRelativeTime(company.freshness ? new Date(company.freshness).toISOString() : "")} update`
        }))
        .sort((a, b) => {
          if (a.trustScore !== null && b.trustScore !== null) {
            return b.trustScore - a.trustScore || b.freshness - a.freshness;
          }
          if (a.trustScore !== null) return -1;
          if (b.trustScore !== null) return 1;
          return b.freshness - a.freshness || a.tie - b.tie;
        });

      communityActionUrl = ranked[0]?.actionUrl || communityActionUrl;

      companyLeaders = {
        best: ranked.slice(0, 3).map(({ name, metric, actionUrl }) => ({ name, metric, actionUrl })),
        worst: ranked.slice(-3).reverse().map(({ name, metric, actionUrl }) => ({ name, metric, actionUrl }))
      };
    } else {
      const derived = deriveCompanyMentions(storyPosts);
      if (derived.best.length > 0 && derived.worst.length > 0) {
        companyLeaders = derived;
      }
    }
  }
}

function estimateImportance(post) {
  const comments = Number.parseInt(post?.comment_count || 0, 10);
  if (comments >= 8) return "critical";
  if (comments >= 4) return "high";
  return "elevated";
}

function estimateEvidenceCount(post) {
  const textLength = decodeHtml(post?.content?.rendered || "").length;
  return Math.max(1, Math.min(35, Math.floor(textLength / 220)));
}

function mapStoryPostToCard(post) {
  const title = decodeHtml(post?.title?.rendered || "Untitled story");
  const summaryText = decodeHtml(post?.excerpt?.rendered || post?.content?.rendered || "");
  const summary = summaryText || "";

  return {
    id: post?.id || 0,
    title,
    summary,
    sourceName: "TSEMOU File",
    sourceUrl: post?.link || "#",
    actionUrl: post?.link || "#",
    updated: toShortRelativeTime(post?.modified || post?.date),
    proofStatus: "Story",
    trustStatus: "",
    companies: "",
    impact: "",
    severity: "",
    timestamp: post?.modified || post?.date || ""
  };
}

async function loadLivingCasesFromBackend() {
  const storyPosts = await fetchWpCollection("story", {
    per_page: 24,
    status: "publish",
    orderby: "modified",
    order: "desc",
    _fields: "id,slug,title,excerpt,content,date,modified,link,comment_count"
  });

  if (storyPosts.length > 1) {
    stories = storyPosts.map(mapStoryPostToCard);
  }
}

function mapPostToFeedItem(post) {
  const timestamp = post?.modified || post?.date_gmt || post?.date || "";
  const embeddedMedia = post?._embedded?.["wp:featuredmedia"];
  const thumb = Array.isArray(embeddedMedia) && embeddedMedia[0] && embeddedMedia[0].source_url
    ? String(embeddedMedia[0].source_url)
    : "";

  return {
    type: "POST",
    title: decodeHtml(post?.title?.rendered || "Untitled post"),
    author: decodeHtml(post?._embedded?.author?.[0]?.name || "Community"),
    time: toShortRelativeTime(timestamp),
    timestamp,
    image: thumb,
    link: post?.link || "#",
    actionUrl: post?.link ? `${post.link}#tsemit` : communityActionUrl
  };
}

async function loadCommunityFeedFromBackend() {
  if (injectedFeedPosts.length > 0) {
    newsItems = injectedFeedPosts.map((item) => ({
      type: "POST",
      title: String(item?.title || ""),
      author: String(item?.author || ""),
      time: String(item?.time || "recently"),
      image: String(item?.image || ""),
      link: String(item?.link || "#"),
      actionUrl: String(item?.actionUrl || "#")
    }));
    return;
  }

  const posts = await fetchWpCollection("posts", {
    per_page: 14,
    status: "publish",
    orderby: "date",
    order: "desc",
    _embed: "author,wp:featuredmedia",
    _fields: "id,title,date,date_gmt,modified,link,_embedded"
  });

  if (posts.length > 0) {
    newsItems = posts.map(mapPostToFeedItem);
  }
}

function updateHeroBrief(stats) {
  const points = document.querySelectorAll(".brief-points li");
  if (!points || points.length < 4) return;

  points[0].textContent = `${stats.cases} Cases evolved`;
  points[1].textContent = `${stats.evidence} New Evidence`;
  points[2].textContent = `${stats.companies} Companies tracked`;
  points[3].textContent = `${stats.graphLinks} Knowledge Graph links`;
}

async function loadKnowledgeGraphStatsFromBackend() {
  const [storiesTotal, evidenceTotal, companiesTotal, relationTotal] = await Promise.all([
    fetchWpCollectionTotals("story", { per_page: 1, status: "publish" }),
    fetchWpCollectionTotals("tsemou_proof", { per_page: 1, status: "publish" }),
    fetchWpCollectionTotals("company", { per_page: 1, status: "publish" }),
    fetchWpCollectionTotals("tsemou_relation", { per_page: 1, status: "publish" })
  ]);

  const estimatedGraphLinks = relationTotal.available
    ? relationTotal.total
    : Math.max(evidenceTotal.total, storiesTotal.total);

  updateHeroBrief({
    cases: storiesTotal.total,
    evidence: evidenceTotal.total,
    companies: companiesTotal.available ? companiesTotal.total : 0,
    graphLinks: estimatedGraphLinks
  });
}

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
              ${item.image ? `<img src="${item.image}" alt="${item.title}">` : ""}
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

function livingCaseTemplate(story, featured = false) {
  const title = story?.title || "Untitled case";
  const summary = story?.summary || "";
  const updated = story?.updated || toShortRelativeTime(story?.timestamp || "");
  const sourceName = story?.sourceName || "";
  const sourceUrl = story?.sourceUrl || story?.actionUrl || story?.link || "#";
  const proofStatus = story?.proofStatus || story?.status || "";
  const trustStatus = story?.trustStatus || "";
  const companies = story?.companies || "";
  const impact = story?.impact || "";
  const severity = story?.severity || "";
  const mediaPayload = pickMediaPayload(story);

  const toReadableValue = (value) => {
    if (value === null || value === undefined) return "";
    if (typeof value === "string") return value.trim();
    if (typeof value === "number") {
      if (value >= 0 && value <= 1) return `${Math.round(value * 100)}%`;
      return `${value}`;
    }
    if (typeof value === "boolean") return value ? "Yes" : "No";
    if (Array.isArray(value)) {
      return value
        .map((item) => toReadableValue(item))
        .filter(Boolean)
        .join(", ");
    }
    if (typeof value === "object") {
      const entries = Object.entries(value)
        .map(([key, raw]) => {
          const label = String(key)
            .replace(/_/g, " ")
            .replace(/\b\w/g, (ch) => ch.toUpperCase());
          const rendered = toReadableValue(raw);
          return rendered ? `${label}: ${rendered}` : "";
        })
        .filter(Boolean);
      return entries.join(" • ");
    }
    return "";
  };

  const companiesText = toReadableValue(companies);
  const impactText = toReadableValue(impact);
  const severityText = toReadableValue(severity);

  const storyTypeText = `${proofStatus} ${title} ${summary}`.toLowerCase();
  let cardType = "case";
  if (storyTypeText.includes("evidence")) {
    cardType = "evidence";
  } else if (storyTypeText.includes("proof") || storyTypeText.includes("verified")) {
    cardType = "proof";
  } else if (storyTypeText.includes("solution")) {
    cardType = "solution";
  } else if (storyTypeText.includes("community")) {
    cardType = "community";
  } else if (storyTypeText.includes("trending") || storyTypeText.includes("discussion") || storyTypeText.includes("thread")) {
    cardType = "trending-discussion";
  }

  const typeBadgeLabelMap = {
    case: "Case",
    evidence: "Evidence",
    proof: "Proof",
    solution: "Solution",
    community: "Community",
    "trending-discussion": "Trending Discussion"
  };
  const typeBadgeIconMap = {
    case: "▪",
    evidence: "🔎",
    proof: "✅",
    solution: "💡",
    community: "🟣",
    "trending-discussion": "🔥"
  };
  const badgeLabel = featured && cardType === "case" ? "Living Case" : typeBadgeLabelMap[cardType];
  const badgeIcon = typeBadgeIconMap[cardType] || "▪";

  const chips = [
    sourceName ? `<span class="case-chip">Source: ${sourceName}</span>` : "",
    proofStatus ? `<span class="case-chip">${proofStatus}</span>` : "",
    trustStatus ? `<span class="case-chip">${trustStatus}</span>` : "",
    impactText ? `<span class="case-chip">Impact: ${impactText}</span>` : "",
    severityText ? `<span class="case-chip">Severity: ${severityText}</span>` : ""
  ].filter(Boolean).join("");

  return `
    <article class="living-case-card case-type-${cardType} ${featured ? "featured" : ""}" tabindex="0">
      <div class="living-case-topline">
        <span class="case-pill type-pill type-pill-${cardType} ${featured ? "featured-pill" : ""}">${badgeIcon} ${badgeLabel}</span>
        <h3 class="living-case-title"><a href="${sourceUrl}">${title}</a></h3>
        <span class="case-updated">Updated ${updated}</span>
      </div>
      ${mediaBlockTemplate(mediaPayload)}
      ${summary ? `<p class="living-case-summary">${summary}</p>` : ""}
      ${chips ? `<div class="living-case-meta">${chips}</div>` : ""}
      <div class="living-case-footer">
        ${companiesText ? `<div class="living-case-footer-copy"><span class="living-case-footer-label">Companies:</span> <span class="living-case-footer-value">${companiesText}</span></div>` : ""}
        <div class="living-case-footer-action">
          <a class="case-action" href="${sourceUrl}">Open Case</a>
        </div>
      </div>
    </article>
  `;
}

function storyTemplate(story) {
  return livingCaseTemplate(story, false);
}

function featuredStoryTemplate(story) {
  return livingCaseTemplate(story, true);
}

function pickMediaPayload(item) {
  const safeItem = item || {};
  if (safeItem.video) return { type: "video", ...safeItem.video };
  if (safeItem.image) return { type: "image", ...safeItem.image };
  if (safeItem.document) return { type: "document", ...safeItem.document };
  if (safeItem.chart) return { type: "chart", ...safeItem.chart };
  if (safeItem.map) return { type: "map", ...safeItem.map };
  if (safeItem.satellite) return { type: "satellite", ...safeItem.satellite };
  if (safeItem.community) return { type: "community", ...safeItem.community };
  if (safeItem.solution) return { type: "solution", ...safeItem.solution };
  if (safeItem.evidence) return { type: "evidence", ...safeItem.evidence };
  return null;
}

function mediaBlockTemplate(media) {
  if (!media || !media.type) return "";

  const title = typeof media.title === "string" ? media.title.trim() : "";
  const source = media.source || "";
  const confidence = media.confidence || "";
  const duration = media.duration || "";
  const votes = media.votes || "";
  const impact = media.impact || "";
  const avatar = media.avatar || "👤";

  const noMediaContent = (label) => `
    <div class="media-empty">
      <span>No media available</span>
      <span>${label} preview</span>
    </div>
  `;

  if (media.type === "video") {
    const body = title ? `▶ ${title}` : noMediaContent("Video");
    return `
      <div class="card-media card-media-video" role="img" aria-label="Video thumbnail preview">
        <div class="media-kicker">Video ${duration ? `• ${duration}` : ""}</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "image") {
    const body = title ? `🖼 ${title}` : noMediaContent("Image");
    return `
      <div class="card-media card-media-image" role="img" aria-label="Hero image preview">
        <div class="media-kicker">Story Hero Image</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "document") {
    const body = title ? `📄 ${title}` : noMediaContent("Document");
    return `
      <div class="card-media card-media-document" role="img" aria-label="Document preview">
        <div class="media-kicker">Document ${source ? `• ${source}` : ""}</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "chart") {
    const body = title ? `📈 ${title}` : noMediaContent("Statistics");
    return `
      <div class="card-media card-media-chart" role="img" aria-label="Statistics chart preview">
        <div class="media-kicker">Statistics</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "map") {
    const body = title ? `🗺 ${title}` : noMediaContent("Map");
    return `
      <div class="card-media card-media-map" role="img" aria-label="Map preview">
        <div class="media-kicker">Map</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "satellite") {
    const body = title ? `🛰 ${title}` : noMediaContent("Satellite");
    return `
      <div class="card-media card-media-satellite" role="img" aria-label="Satellite preview">
        <div class="media-kicker">Satellite</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "community") {
    const body = title ? `${avatar} ${title}` : noMediaContent("Community");
    return `
      <div class="card-media card-media-community" role="img" aria-label="Community preview">
        <div class="media-kicker">Community Highlight</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "solution") {
    const body = title ? `💡 ${title}` : noMediaContent("Solution");
    return `
      <div class="card-media card-media-solution" role="img" aria-label="Solution preview">
        <div class="media-kicker">Solution ${votes ? `• ${votes} votes` : ""}${impact ? ` • ${impact}` : ""}</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  if (media.type === "evidence") {
    const body = title ? `🔎 ${title}` : noMediaContent("Evidence");
    return `
      <div class="card-media card-media-evidence" role="img" aria-label="Evidence preview">
        <div class="media-kicker">Evidence ${source ? `• ${source}` : ""}${confidence ? ` • ${confidence}` : ""}</div>
        <div class="media-canvas">${body}</div>
      </div>
    `;
  }

  return "";
}

const mvtMixedCards = [
  {
    kind: "gold-solution",
    badge: "Gold Community Solution",
    title: "Neighborhood cooling route adopted by three districts",
    description: "Residents co-designed a heat-response route that reduced emergency wait times during peak afternoons.",
    ctaLabel: "View Solution",
    solution: {
      title: "Cooling route simulation",
      votes: 248,
      impact: "High"
    }
  },
  {
    kind: "silver-highlight",
    badge: "Silver Highlight",
    title: "Local audit team publishes supplier conflict map",
    description: "A volunteer review surfaced repeat contract links and published a clear conflict trail for public scrutiny.",
    ctaLabel: "Read Highlight",
    evidence: {
      title: "Supplier conflict map",
      source: "Civic Audit Hub",
      confidence: "88% confidence"
    },
    chart: {
      title: "Conflict concentration by district"
    }
  },
  {
    kind: "bronze-discovery",
    badge: "Bronze Discovery",
    title: "Community logs reveal shipment timing anomalies",
    description: "Field notes found a recurring delivery gap pattern that now guides the next verification wave.",
    ctaLabel: "Open Discovery",
    satellite: {
      title: "Night route signal overlap"
    }
  },
  {
    kind: "community-highlight",
    badge: "Community Highlight",
    title: "Citizen monitors coordinate cross-city evidence handoff",
    description: "Independent contributors aligned format and timing so signals from multiple neighborhoods can be compared quickly.",
    ctaLabel: "See Community Work",
    community: {
      title: "Volunteer relay update",
      avatar: "👥"
    },
    document: {
      source: "Community Ledger"
    }
  },
  {
    kind: "trending-discussion",
    badge: "Trending Discussion",
    title: "Public thread on remediation priorities gains momentum",
    description: "Community voting is converging around the first two interventions to test before escalation.",
    ctaLabel: "Join Discussion",
    video: {
      title: "Live discussion recap",
      duration: "02:14"
    },
    map: {
      title: "Hotspot thread map"
    }
  }
];

function mixedCardTemplate(card) {
  const safeCard = card || {};
  const safeKind = safeCard.kind || "community-highlight";
  const safeBadge = safeCard.badge || "Community Highlight";
  const safeTitle = safeCard.title || "Community update";
  const safeDescription = safeCard.description || "Preview content";
  const safeCtaLabel = safeCard.ctaLabel || "Open";
  const mediaPayload = pickMediaPayload(safeCard);

  const mixedTypeMap = {
    "gold-solution": "solution",
    "silver-highlight": "evidence",
    "bronze-discovery": "proof",
    "community-highlight": "community",
    "trending-discussion": "trending-discussion"
  };
  const mixedIconMap = {
    "gold-solution": "🥇",
    "silver-highlight": "🥈",
    "bronze-discovery": "🥉",
    "community-highlight": "🟣",
    "trending-discussion": "🔥"
  };
  const mixedType = mixedTypeMap[safeKind] || "community";
  const mixedIcon = mixedIconMap[safeKind] || "▪";

  return `
    <article class="living-case-card mvt-mixed-card case-type-${mixedType} mvt-${safeKind}" tabindex="0">
      <div class="living-case-topline">
        <span class="case-pill mvt-mixed-pill type-pill type-pill-${mixedType}">${mixedIcon} ${safeBadge}</span>
        <span class="mvt-mini-badge">DEMO</span>
      </div>
      ${mediaBlockTemplate(mediaPayload)}
      <h3 class="living-case-title">${safeTitle}</h3>
      <p class="living-case-summary">${safeDescription}</p>
      <div class="living-case-actions">
        <a class="case-action" href="#">${safeCtaLabel}</a>
      </div>
    </article>
  `;
}

function buildMixedCaseStream(storyItems, applyMixedCards) {
  const html = [];
  const cadence = 6;

  storyItems.forEach((story, index) => {
    html.push(index === 0 ? featuredStoryTemplate(story) : storyTemplate(story));

    if (!applyMixedCards) return;
    if ((index + 1) % cadence !== 0) return;

    const mixedCard = mvtMixedCards[Math.floor(index / cadence) % mvtMixedCards.length];
    html.push(mixedCardTemplate(mixedCard));
  });

  return html.join("");
}

function newsTemplate(item) {
  const safeType = item && item.type ? String(item.type) : "POST";
  const safeTitle = item && item.title ? String(item.title) : "Untitled post";
  const safeAuthor = item && item.author ? String(item.author) : "Unknown";
  const safeTime = item && item.time ? String(item.time) : "recently";
  const safeLink = item && item.link ? String(item.link) : "#";
  const safeActionUrl = item && item.actionUrl ? String(item.actionUrl) : communityActionUrl;
  const typeClass = `type-${safeType.toLowerCase()}`;
  let imageMarkup = "";
  if (item && item.image) {
    imageMarkup = `<img class="entry-thumb" src="${item.image}" alt="${safeType} thumbnail">`;
  }
  return `
    <article class="news-item community-entry" tabindex="0">
      <div>
        <div class="entry-head">
          <span class="entry-type ${typeClass}">${safeType}</span>
        </div>
        <h4 class="entry-title">${safeTitle}</h4>
        <p class="entry-author">${safeAuthor} • ${safeTime}</p>
        ${imageMarkup}
        <p class="entry-link-wrap"><a href="${safeLink}">Open</a></p>
        <div class="entry-actions">
          <button class="vote-btn tsemit-action" data-action-url="${safeActionUrl}" type="button" aria-label="TSEMIT this ${safeType.toLowerCase()}">TSEMIT</button>
          <button class="vote-btn untsemit-action" data-action-url="${safeActionUrl}" type="button" aria-label="UNTSEMIT this ${safeType.toLowerCase()}">UNTSEMIT</button>
        </div>
      </div>
    </article>
  `;
}

function wireCommunityActions() {
  document.addEventListener("click", (event) => {
    const button = event.target.closest(".tsemit-btn, .tsemit-action, .untsemit-action");
    if (!button) return;

    const actionUrl = button.dataset.actionUrl || communityActionUrl || "#";
    if (!actionUrl || actionUrl === "#") return;

    window.location.href = actionUrl;
  });
}

function renderStories(filter = "") {
  const needle = filter.trim().toLowerCase();
  const filtered = stories.filter((story) => {
    if (!needle) return true;
    const title = String(story.title || "").toLowerCase();
    const summary = String(story.summary || "").toLowerCase();
    const sourceName = String(story.sourceName || "").toLowerCase();
    const proofStatus = String(story.proofStatus || story.status || "").toLowerCase();
    const companies = String(story.companies || "").toLowerCase();
    const severity = String(story.severity || "").toLowerCase();
    return (
      title.includes(needle) ||
      summary.includes(needle) ||
      sourceName.includes(needle) ||
      proofStatus.includes(needle) ||
      companies.includes(needle) ||
      severity.includes(needle)
    );
  });

  storyFeed.innerHTML = buildMixedCaseStream(filtered, needle === "");
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

async function initializeHomepage() {
  await loadCommunityRankingsFromBackend();
  await loadLivingCasesFromBackend();
  await loadCommunityFeedFromBackend();
  await loadKnowledgeGraphStatsFromBackend();
  renderCommunity();
  renderStories();
  renderNews();
}

initializeHomepage();
wireCommunityActions();
