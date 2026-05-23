const questions = Array.isArray(window.AulaNetQuestions) ? window.AulaNetQuestions : [];

function createLucideIcon(name) {
    const icon = document.createElement("i");
    icon.dataset.lucide = name;
    return icon;
}

function createStatItem(iconName, value, label) {
    const statItem = document.createElement("div");
    statItem.className = "stat-item";

    const icon = createLucideIcon(iconName);

    const valueSpan = document.createElement("span");
    valueSpan.className = "stat-value";
    valueSpan.textContent = String(value);

    const labelSpan = document.createElement("span");
    labelSpan.className = "stat-label";
    labelSpan.textContent = label;

    statItem.append(icon, valueSpan, labelSpan);
    return statItem;
}

function createSubjectTags(tags) {
    const tagsContainer = document.createElement("div");
    const tagList = Array.isArray(tags) && tags.length > 0 ? tags : ["General"];

    tagList.forEach((tagName) => {
        const tag = document.createElement("span");
        tag.className = "tag";
        tag.textContent = tagName;
        tagsContainer.appendChild(tag);
    });

    return tagsContainer;
}

function createQuestionCard(question) {
    const card = document.createElement("div");
    card.className = "question-card";

    const content = document.createElement("div");
    content.className = "question-content";

    const stats = document.createElement("div");
    stats.className = "question-stats";
    stats.append(
        createStatItem("message-square", question.answers, "answers"),
        createStatItem("eye", question.views, "views"),
    );

    const main = document.createElement("div");
    main.className = "question-main";

    const titleLink = document.createElement("a");
    titleLink.className = "question-title";
    titleLink.href = `./pages/question.php?id=${question.id}`;
    titleLink.textContent = question.title;

    const description = document.createElement("p");
    description.className = "question-description";
    description.textContent = question.description;

    const tags = document.createElement("div");
    tags.className = "question-tags";
    tags.appendChild(createSubjectTags(question.tags || [question.subject || "General"]));

    const meta = document.createElement("div");
    meta.className = "question-meta";

    const metaLeft = document.createElement("div");
    metaLeft.className = "meta-left";

    const askedBy = document.createElement("span");
    askedBy.append("asked by ");

    const author = document.createElement("span");
    author.className = "author";
    author.textContent = question.author;
    askedBy.appendChild(author);

    const timeAgo = document.createElement("span");
    timeAgo.textContent = question.timeAgo;

    metaLeft.append(askedBy, timeAgo);

    const metaRight = document.createElement("div");
    metaRight.className = "meta-right";

    const eyeIcon = createLucideIcon("eye");

    const views = document.createElement("span");
    views.textContent = `${question.views} views`;

    metaRight.append(eyeIcon, views);
    meta.append(metaLeft, metaRight);

    main.append(titleLink, description, tags, meta);
    content.append(stats, main);
    card.appendChild(content);

    return card;
}

function renderRecentQuestions() {
    const questionsList = document.getElementById("questionsList");
    if (!questionsList) {
        return;
    }

    if (questions.length === 0) {
        const emptyCard = document.createElement("div");
        emptyCard.className = "question-card";

        const emptyText = document.createElement("p");
        emptyText.className = "question-description";
        emptyText.textContent = "No questions have been posted yet.";

        emptyCard.appendChild(emptyText);
        questionsList.replaceChildren(emptyCard);
    } else {
        const cards = questions.map((question) => createQuestionCard(question));
        questionsList.replaceChildren(...cards);
    }

    const countElement = document.querySelector(".question-count");
    if (countElement) {
        const label = questions.length === 1 ? "question" : "questions";
        countElement.textContent = `${questions.length} ${label}`;
    }

    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderRecentQuestions);
} else {
    renderRecentQuestions();
}