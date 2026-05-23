const questions = Array.isArray(window.AulaNetQuestions) ? window.AulaNetQuestions : [];
const subjects = Array.isArray(window.AulaNetSubjects) ? window.AulaNetSubjects : [];
const stats = window.AulaNetStats && typeof window.AulaNetStats === "object" ? window.AulaNetStats : {};

const pageSize = 5;
let activeSubject = "all";
let currentSort = "recent";
let currentSearch = "";
let currentPage = 1;

function createLucideIcon(name) {
    const icon = document.createElement("i");
    icon.dataset.lucide = name;
    return icon;
}

function createStatItem(iconName, value, label) {
    const statItem = document.createElement("div");
    statItem.className = "stat-item-card";

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

function createQuestionTags(tags) {
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

    const statsColumn = document.createElement("div");
    statsColumn.className = "question-stats";
    statsColumn.append(
        createStatItem("message-square", question.answers ?? 0, "answers"),
        createStatItem("eye", question.views ?? 0, "views"),
    );

    const main = document.createElement("div");
    main.className = "question-main";

    const titleLink = document.createElement("a");
    titleLink.className = "question-title";
    titleLink.href = `./question.php?id=${question.id}`;
    titleLink.textContent = question.title ?? "Untitled question";

    const description = document.createElement("p");
    description.className = "question-description";
    description.textContent = question.description ?? "";

    const tags = document.createElement("div");
    tags.className = "question-tags";
    tags.appendChild(createQuestionTags(question.tags || [question.subject]));

    const meta = document.createElement("div");
    meta.className = "question-meta";

    const metaLeft = document.createElement("div");
    metaLeft.className = "meta-left";

    const askedBy = document.createElement("span");
    askedBy.append("asked by ");

    const author = document.createElement("span");
    author.className = "author";
    author.textContent = question.author ?? "Anonymous";
    askedBy.appendChild(author);

    const timeAgo = document.createElement("span");
    timeAgo.textContent = question.timeAgo ?? "just now";

    metaLeft.append(askedBy, timeAgo);

    const metaRight = document.createElement("div");
    metaRight.className = "meta-right";

    const eyeIcon = createLucideIcon("eye");
    const views = document.createElement("span");
    views.textContent = `${question.views ?? 0} views`;

    metaRight.append(eyeIcon, views);
    meta.append(metaLeft, metaRight);

    main.append(titleLink, description, tags, meta);
    content.append(statsColumn, main);
    card.appendChild(content);

    return card;
}

function getFilteredQuestions() {
    return questions.filter((question) => {
        const matchesSubject = activeSubject === "all" || question.subject === activeSubject;
        if (!matchesSubject) {
            return false;
        }

        if (!currentSearch) {
            return true;
        }

        const searchableText = [
            question.title,
            question.description,
            question.author,
            question.subject,
        ]
            .join(" ")
            .toLowerCase();

        return searchableText.includes(currentSearch);
    });
}

function sortQuestions(questionList) {
    const sorted = [...questionList];

    switch (currentSort) {
        case "votes":
            sorted.sort((a, b) => (b.answers ?? 0) - (a.answers ?? 0));
            break;
        case "answers":
            sorted.sort((a, b) => (b.answers ?? 0) - (a.answers ?? 0));
            break;
        case "views":
            sorted.sort((a, b) => (b.views ?? 0) - (a.views ?? 0));
            break;
        default:
            sorted.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
            break;
    }

    return sorted;
}

function updateQuestionCount(count) {
    const countElement = document.querySelector(".question-count");
    if (!countElement) {
        return;
    }

    const label = count === 1 ? "question" : "questions";
    countElement.textContent = `${count} ${label}`;
}

function getTotalPages(count) {
    return Math.max(1, Math.ceil(count / pageSize));
}

function clampCurrentPage(totalPages) {
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);
}

function renderPagination(totalQuestions) {
    const pagination = document.getElementById("questionPagination");
    if (!pagination) {
        return;
    }

    const totalPages = getTotalPages(totalQuestions);
    clampCurrentPage(totalPages);

    if (totalQuestions <= pageSize) {
        pagination.replaceChildren();
        pagination.style.display = "none";
        return;
    }

    pagination.style.display = "flex";
    pagination.replaceChildren();

    const createButton = (label, page, options = {}) => {
        const button = document.createElement("button");
        button.className = "pagination-btn";
        button.textContent = label;

        if (options.active) {
            button.classList.add("active");
        }

        if (options.hidden) {
            button.hidden = true;
        }

        if (typeof page === "number") {
            button.addEventListener("click", () => {
                currentPage = page;
                renderQuestions();
            });
        }

        return button;
    };

    pagination.appendChild(createButton("Previous", currentPage - 1, { hidden: currentPage === 1 }));

    for (let page = 1; page <= totalPages; page += 1) {
        pagination.appendChild(createButton(String(page), page, { active: page === currentPage }));
    }

    pagination.appendChild(createButton("Next", currentPage + 1, { hidden: currentPage === totalPages }));
}

function updateStats() {
    const questionStat = document.getElementById("questionStat");
    const answerStat = document.getElementById("answerStat");
    const userStat = document.getElementById("userStat");

    if (questionStat) {
        questionStat.textContent = String(stats.questions ?? questions.length ?? 0);
    }

    if (answerStat) {
        answerStat.textContent = String(stats.answers ?? 0);
    }

    if (userStat) {
        userStat.textContent = String(stats.users ?? 0);
    }
}

function renderQuestions() {
    const listElement = document.getElementById("questionsList");
    if (!listElement) {
        return;
    }

    const visibleQuestions = sortQuestions(getFilteredQuestions());
    updateQuestionCount(visibleQuestions.length);
    renderPagination(visibleQuestions.length);

    const startIndex = (currentPage - 1) * pageSize;
    const pagedQuestions = visibleQuestions.slice(startIndex, startIndex + pageSize);

    if (pagedQuestions.length === 0) {
        const emptyCard = document.createElement("div");
        emptyCard.className = "question-card";

        const emptyText = document.createElement("p");
        emptyText.className = "question-description";
        emptyText.textContent = "No questions found for the selected filters.";

        emptyCard.appendChild(emptyText);
        listElement.replaceChildren(emptyCard);
    } else {
        const cards = pagedQuestions.map((question) => createQuestionCard(question));
        listElement.replaceChildren(...cards);
    }

    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }
}
function renderSubjectButtons() {
    const list = document.getElementById("subjectList");
    if (!list) {
        return;
    }

    list.replaceChildren();

    const allItem = document.createElement("li");
    const allButton = document.createElement("button");
    allButton.className = "subject-btn active";
    allButton.dataset.subject = "all";
    allButton.textContent = "All Subjects";
    allItem.appendChild(allButton);
    list.appendChild(allItem);

    subjects.forEach((subject) => {
        const item = document.createElement("li");
        const button = document.createElement("button");
        button.className = "subject-btn";
        button.dataset.subject = subject.name;
        button.textContent = subject.name;
        item.appendChild(button);
        list.appendChild(item);
    });
}

function syncSubjectButtonState() {
    const subjectButtons = document.querySelectorAll(".subject-btn");

    subjectButtons.forEach((button) => {
        button.addEventListener("click", function onSubjectClick() {
            subjectButtons.forEach((btn) => btn.classList.remove("active"));
            this.classList.add("active");

            activeSubject = this.dataset.subject || "all";
            currentPage = 1;
            renderQuestions();
        });
    });
}

function setupSearchInput() {
    const searchInput = document.querySelector(".search-input");
    if (!searchInput) {
        return;
    }

    searchInput.addEventListener("input", (event) => {
        currentSearch = event.target.value.trim().toLowerCase();
        currentPage = 1;
        renderQuestions();
    });
}

function setupSortSelect() {
    const sortSelect = document.querySelector(".sort-select");
    if (!sortSelect) {
        return;
    }

    sortSelect.addEventListener("change", (event) => {
        currentSort = event.target.value;
        currentPage = 1;
        renderQuestions();
    });
}

function initHomeQuestions() {
    renderSubjectButtons();
    syncSubjectButtonState();
    setupSearchInput();
    updateStats();
    renderQuestions();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHomeQuestions);
} else {
    initHomeQuestions();
}
