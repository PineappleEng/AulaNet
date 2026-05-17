const questions = [
    {
        id: 1,
        title: "How to implement binary search trees in Java?",
        description:
            "I'm working on my Data Structures assignment and need help understanding how to implement a balanced binary search tree. Can someone explain the rotation operations?",
        tags: ["Data Structures", "Java", "Algorithms"],
        author: "Ana Silva",
        timeAgo: "5d ago",
        votes: 15,
        answers: 4,
        views: 234,
    },
    {
        id: 2,
        title: "Best practices for database normalization?",
        description:
            "What are the main principles to follow when normalizing a database schema? I'm struggling with 3NF and BCNF.",
        tags: ["Database", "SQL", "Design"],
        author: "Carlos Mendes",
        timeAgo: "6d ago",
        votes: 23,
        answers: 7,
        views: 456,
    },
    {
        id: 3,
        title: "Understanding RESTful API design principles",
        description:
            "I'm building my first REST API for a course project. What are the key principles I should follow for proper resource naming and HTTP method usage?",
        tags: ["Web Development", "API", "REST"],
        author: "Joao Santos",
        timeAgo: "7d ago",
        votes: 18,
        answers: 5,
        views: 312,
    },
    {
        id: 4,
        title: "Time complexity of recursive algorithms",
        description:
            "Can someone help me understand how to calculate the time complexity of recursive algorithms using the Master Theorem?",
        tags: ["Algorithms", "Complexity", "Theory"],
        author: "Ana Silva",
        timeAgo: "8d ago",
        votes: 12,
        answers: 3,
        views: 189,
    },
    {
        id: 5,
        title: "React hooks vs class components",
        description:
            "What are the advantages of using hooks over class components in React? When should I prefer one over the other?",
        tags: ["React", "JavaScript", "Web Development"],
        author: "Carlos Mendes",
        timeAgo: "9d ago",
        votes: 31,
        answers: 9,
        views: 678,
    },
];

let activeSubject = "all";
let currentSort = "recent";
let currentSearch = "";

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

function createQuestionCard(question) {
    const card = document.createElement("div");
    card.className = "question-card";

    const content = document.createElement("div");
    content.className = "question-content";

    const stats = document.createElement("div");
    stats.className = "question-stats";
    stats.append(
        createStatItem("trending-up", question.votes, "votes"),
        createStatItem("message-square", question.answers, "answers"),
    );

    const main = document.createElement("div");
    main.className = "question-main";

    const titleLink = document.createElement("a");
    titleLink.className = "question-title";
    titleLink.href = `./question.php?id=${question.id}`;
    titleLink.textContent = question.title;

    const description = document.createElement("p");
    description.className = "question-description";
    description.textContent = question.description;

    const tags = document.createElement("div");
    tags.className = "question-tags";

    question.tags.forEach((tagName) => {
        const tag = document.createElement("span");
        tag.className = "tag";
        tag.textContent = tagName;
        tags.appendChild(tag);
    });

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

function getFilteredQuestions() {
    return questions.filter((question) => {
        const bySubject =
            activeSubject === "all" || question.tags.includes(activeSubject);

        if (!bySubject) {
            return false;
        }

        if (!currentSearch) {
            return true;
        }

        const searchableText = [
            question.title,
            question.description,
            question.author,
            ...question.tags,
        ]
            .join(" ")
            .toLowerCase();

        return searchableText.includes(currentSearch);
    });
}

function sortQuestions(questionList) {
    if (currentSort === "recent") {
        return questionList;
    }

    const sorted = [...questionList];

    switch (currentSort) {
        case "votes":
            sorted.sort((a, b) => b.votes - a.votes);
            break;
        case "answers":
            sorted.sort((a, b) => b.answers - a.answers);
            break;
        case "views":
            sorted.sort((a, b) => b.views - a.views);
            break;
        default:
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

function renderQuestions() {
    const listElement = document.getElementById("questionsList");
    if (!listElement) {
        return;
    }

    const visibleQuestions = sortQuestions(getFilteredQuestions());
    updateQuestionCount(visibleQuestions.length);

    if (visibleQuestions.length === 0) {
        const emptyCard = document.createElement("div");
        emptyCard.className = "question-card";

        const emptyText = document.createElement("p");
        emptyText.className = "question-description";
        emptyText.textContent = "No questions found for the selected filters.";

        emptyCard.appendChild(emptyText);
        listElement.replaceChildren(emptyCard);
    } else {
        const cards = visibleQuestions.map((question) => createQuestionCard(question));
        listElement.replaceChildren(...cards);
    }

    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }
}

function setupSubjectFilterButtons() {
    const subjectButtons = document.querySelectorAll(".subject-btn");

    subjectButtons.forEach((button) => {
        button.addEventListener("click", function onSubjectClick() {
            subjectButtons.forEach((btn) => btn.classList.remove("active"));
            this.classList.add("active");

            activeSubject = this.dataset.subject || "all";
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
        renderQuestions();
    });
}

function initHomeQuestions() {
    setupSubjectFilterButtons();
    setupSearchInput();
    setupSortSelect();
    renderQuestions();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHomeQuestions);
} else {
    initHomeQuestions();
}
