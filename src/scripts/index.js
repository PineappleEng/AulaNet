const recentQuestions = [
  {
    title: "How to implement binary search trees in Java?",
    preview:
      "I'm working on my Data Structures assignment and need help understanding how to implement a balanced binary search tree. Can someone explain the rotation operations?",
    tags: ["Java", "Data Structures", "Binary Trees"],
    askedBy: "Marina Costa",
    upvotes: 42,
    answers: 11,
    views: 324,
  },
  {
    title: "Best practices for database normalization?",
    preview: "What are the main principles to follow when normalizing a database schema? I'm struggling with 3NF and BCNF.",
    tags: ["Database", "SQL", "Design"],
    askedBy: "Carlos Mendez",
    upvotes: 28,
    answers: 7,
    views: 210,
  }
];

function createIcon(name) {
  const icon = document.createElement("i");
  icon.className = "icon";
  icon.dataset.lucide = name;
  return icon;
}

function createMetric(iconName, value) {
  const metric = document.createElement("div");
  metric.className = "metric";

  const valueText = document.createElement("span");
  valueText.textContent = String(value);

  metric.append(createIcon(iconName), valueText);
  return metric;
}

function createQuestionCard(question) {
  const card = document.createElement("div");
  card.className = "card question-preview-card";

  const metrics = document.createElement("aside");
  metrics.className = "question-metrics";
  metrics.setAttribute("aria-label", "Question stats");
  metrics.append(
    createMetric("trending-up", question.upvotes),
    createMetric("message-square", question.answers),
  );

  const details = document.createElement("div");
  details.className = "question-details";

  const title = document.createElement("h3");
  title.textContent = question.title;

  const preview = document.createElement("p");
  preview.textContent = question.preview;

  const tags = document.createElement("div");
  tags.className = "question-tags";
  tags.setAttribute("aria-label", "Question tags");

  question.tags.forEach((tagName) => {
    const tag = document.createElement("span");
    tag.className = "tag";
    tag.textContent = tagName;
    tags.appendChild(tag);
  });

  const meta = document.createElement("div");
  meta.className = "question-meta";

  const author = document.createElement("span");
  author.textContent = `Asked by ${question.askedBy}`;

  const views = document.createElement("span");
  views.className = "views-meta";
  views.setAttribute("aria-label", `${question.views} views`);

  const viewsValue = document.createElement("span");
  viewsValue.className = "meta-value";
  viewsValue.textContent = String(question.views);

  const viewsLabel = document.createElement("span");
  viewsLabel.className = "meta-label";
  viewsLabel.textContent = "views";

  views.append(createIcon("eye"), viewsValue, viewsLabel);
  meta.append(author, views);

  details.append(title, preview, tags, meta);
  card.append(metrics, details);

  return card;
}

function renderQuestionPreviews() {
  const previewContainer = document.getElementById("questions-preview");
  if (!previewContainer) {
    return;
  }

  previewContainer.innerHTML = "";
  recentQuestions.forEach((question) => {
    previewContainer.appendChild(createQuestionCard(question));
  });
}

renderQuestionPreviews();
