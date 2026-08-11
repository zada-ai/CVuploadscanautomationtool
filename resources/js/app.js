const cvDatabase = [
    {
        id: 1,
        name: 'Avery Carter',
        email: 'avery.carter@example.com',
        phone: '+1 555 987 6543',
        location: 'New York, USA',
        title: 'Civil Engineer',
        summary: 'Driven civil engineer with experience designing safe and sustainable infrastructure projects.',
        experience: [
            {
                company: 'Metro Construction',
                role: 'Project Engineer',
                duration: '2022 - Present',
                description: 'Managed site inspections and supported structural design for urban bridges.'
            },
            {
                company: 'Urban Build Co.',
                role: 'Junior Civil Engineer',
                duration: '2019 - 2022',
                description: 'Assisted with roadway design and performed hydrology analysis for residential developments.'
            }
        ],
        education: [
            {
                institution: 'State University',
                degree: 'B.Sc. Civil Engineering',
                year: '2019'
            }
        ],
        skills: ['AutoCAD', 'Revit', 'Project Scheduling', 'Roadway Design'],
        projects: [
            {
                title: 'Park Avenue Bridge Retrofit',
                description: 'Coordinated a successful rehabilitation plan for a high-traffic urban bridge.'
            }
        ]
    }
];

const uploadForm = document.getElementById('uploadForm');
const fullNameInput = document.getElementById('fullName');
const jobTitleInput = document.getElementById('jobTitle');
const emailInput = document.getElementById('email');
const phoneInput = document.getElementById('phone');
const cvFileInput = document.getElementById('cvFile');
const uploadStatus = document.getElementById('uploadStatus');
const previewContainer = document.getElementById('previewContainer');
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const noResults = document.getElementById('noResults');
const modalOverlay = document.getElementById('modalOverlay');
const modalName = document.getElementById('modalName');
const modalTitle = document.getElementById('modalTitle');
const modalEmail = document.getElementById('modalEmail');
const modalPhone = document.getElementById('modalPhone');
const modalLocation = document.getElementById('modalLocation');
const modalSummary = document.getElementById('modalSummary');
const modalExperience = document.getElementById('modalExperience');
const modalEducation = document.getElementById('modalEducation');
const modalSkills = document.getElementById('modalSkills');
const modalProjects = document.getElementById('modalProjects');
const modalClose = document.getElementById('modalClose');
const fileNameText = document.getElementById('fileName');
const searchForm = document.getElementById('search-form');
const searchPreview = document.getElementById('searchPreview');
const searchTerm = document.getElementById('searchTerm');
const candidateTableBody = document.getElementById('candidateTableBody');

let nextId = 2;

renderCandidateTable();
renderSearchResults();

uploadForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!cvFileInput?.files.length) {
        showStatus('Please choose a PDF file before uploading.', false);
        return;
    }

    showStatus('Scanning CV... Please wait.', true, true);
    await delay(2000);

    const parsedCv = generateParsedCv({
        name: fullNameInput.value.trim(),
        title: jobTitleInput.value.trim(),
        email: emailInput.value.trim(),
        phone: phoneInput.value.trim()
    });

    cvDatabase.push(parsedCv);
    renderParsedPreview(parsedCv);
    showStatus('CV Uploaded & Scanned Successfully', true, false);
    renderSearchResults(searchInput.value.trim());
    renderCandidateTable();
    uploadForm.reset();
    fileNameText.textContent = 'PDF, DOC, DOCX • Max file size: 10MB';
});

cvFileInput?.addEventListener('change', () => {
    const file = cvFileInput.files[0];
    fileNameText.textContent = file ? file.name : 'PDF, DOC, DOCX • Max file size: 10MB';
});

searchInput?.addEventListener('input', () => {
    renderSearchResults(searchInput.value.trim());
});

searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = searchInput.value.trim();
    searchPreview?.classList.toggle('hidden', !value);
    if (value) {
        searchTerm.textContent = value;
    }
    renderSearchResults(value);
});

modalClose?.addEventListener('click', () => {
    closeModal();
});

modalOverlay?.addEventListener('click', (event) => {
    if (event.target === modalOverlay) {
        closeModal();
    }
});

function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function showStatus(message, success = true, loading = false) {
    if (!uploadStatus) return;
    uploadStatus.classList.remove('hidden');
    uploadStatus.textContent = `${loading ? 'Processing' : success ? 'Success' : 'Error'}: ${message}`;
    uploadStatus.className = `rounded-3xl border px-4 py-3 text-sm ${success ? 'border-slate-200 bg-slate-50 text-slate-800' : 'border-rose-200 bg-rose-50 text-rose-700'}`;
}

function generateParsedCv({ name, title, email, phone }) {
    const normalizedTitle = title || 'Civil Engineer';
    const candidateName = name || 'Jordan Blake';
    const summary = `Experienced ${normalizedTitle} with a strong track record of delivering infrastructure and construction projects on time and on budget.`;

    return {
        id: nextId++,
        name: candidateName,
        email: email || 'candidate@example.com',
        phone: phone || '+1 555 321 9876',
        location: 'San Francisco, CA',
        title: normalizedTitle,
        summary,
        experience: [
            {
                company: 'Riverfront Planning Group',
                role: `${normalizedTitle}`,
                duration: '2024 - Present',
                description: 'Led feasibility studies, coordinated civil design packages, and collaborated with stakeholders across municipal teams.'
            },
            {
                company: 'Pacific Engineering Partners',
                role: 'Site Engineer',
                duration: '2021 - 2024',
                description: 'Supported the construction of highway expansions, managed permit submissions, and monitored quality compliance.'
            }
        ],
        education: [
            {
                institution: 'Technical Institute',
                degree: 'B.Eng. Civil Engineering',
                year: '2021'
            },
            {
                institution: 'Professional Development Academy',
                degree: 'Project Management Certification',
                year: '2023'
            }
        ],
        skills: ['Site Analysis', 'Structural Modeling', 'Drainage Design', 'Contract Coordination', 'AutoCAD'],
        projects: [
            {
                title: 'Downtown Traffic Relief Plan',
                description: 'Designed a traffic optimization scheme for an urban corridor with sustainability improvements.'
            },
            {
                title: 'Residential Stormwater System',
                description: 'Delivered a low-impact drainage solution for a multi-block development.'
            }
        ]
    };
}

function renderParsedPreview(cvData) {
    const previewName = document.getElementById('previewName');
    const previewTitle = document.getElementById('previewTitle');
    const previewSummary = document.getElementById('previewSummary');
    const previewExperience = document.getElementById('previewExperience');
    const previewEducation = document.getElementById('previewEducation');
    const previewSkills = document.getElementById('previewSkills');
    const previewProjects = document.getElementById('previewProjects');

    if (!previewContainer || !previewName || !previewTitle) return;

    previewContainer.classList.remove('hidden');
    previewName.textContent = cvData.name;
    previewTitle.textContent = cvData.title;
    previewSummary.textContent = cvData.summary;
    previewExperience.innerHTML = cvData.experience.map(exp => `<li><strong>${exp.role}</strong> at ${exp.company} — ${exp.duration}<br>${exp.description}</li>`).join('');
    previewEducation.innerHTML = cvData.education.map(item => `<li>${item.degree}, ${item.institution} (${item.year})</li>`).join('');
    previewSkills.innerHTML = cvData.skills.map(skill => `<li>${skill}</li>`).join('');
    previewProjects.innerHTML = cvData.projects.map(project => `<li><strong>${project.title}</strong> — ${project.description}</li>`).join('');
}

function renderSearchResults(filter = '') {
    if (!searchResults || !noResults) return;

    const normalizedFilter = filter.toLowerCase();
    const matched = cvDatabase.filter((entry) => entry.title.toLowerCase().includes(normalizedFilter));

    searchResults.innerHTML = '';

    if (!matched.length) {
        noResults.classList.remove('hidden');
        return;
    }

    noResults.classList.add('hidden');
    matched.forEach((entry) => {
        const experienceTotal = entry.experience.length;
        const card = document.createElement('div');
        card.className = 'rounded-[26px] border border-slate-200 bg-white p-6 shadow-sm';
        card.innerHTML = `
            <div class="space-y-3">
                <h3 class="text-xl font-semibold text-slate-900">${entry.name}</h3>
                <p class="text-sm text-slate-500"><strong>${entry.title}</strong></p>
                <p class="text-sm text-slate-600">Total Experience: ${experienceTotal} roles</p>
                <p class="text-sm text-slate-600">${entry.summary}</p>
            </div>
            <div class="mt-6 text-right">
                <button type="button" class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700" data-id="${entry.id}">View Full CV</button>
            </div>
        `;

        const button = card.querySelector('button');
        button?.addEventListener('click', () => openCvModal(entry.id));
        searchResults.appendChild(card);
    });
}

function renderCandidateTable() {
    if (!candidateTableBody) return;

    candidateTableBody.innerHTML = cvDatabase.map((entry) => `
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-700 font-semibold">${entry.name.split(' ').map((word) => word[0]).join('').slice(0, 2)}</div>
                    <div>
                        <p class="font-semibold text-slate-900">${entry.name}</p>
                        <p class="text-sm text-slate-500">${entry.title}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-4 text-sm text-slate-600">
                <p>${entry.email}</p>
                <p>${entry.phone}</p>
            </td>
            <td class="px-4 py-4 text-slate-600">${entry.title}</td>
            <td class="px-4 py-4 text-slate-600">${entry.experience.length} Years</td>
            <td class="px-4 py-4 text-slate-600">${entry.skills.map((skill) => `<span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">${skill}</span>`).join(' ')}</td>
            <td class="px-4 py-4 text-slate-600">${entry.education[0]?.degree || ''}</td>
            <td class="px-4 py-4 text-slate-600">2 May 2024</td>
            <td class="px-4 py-4">
                <button type="button" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" onclick="openCvModal(${entry.id})">View CV</button>
            </td>
        </tr>
    `).join('');
}

function openCvModal(id) {
    const entry = cvDatabase.find((item) => item.id === id);
    if (!entry || !modalOverlay) return;

    modalName.textContent = entry.name;
    modalTitle.textContent = entry.title;
    modalEmail.textContent = entry.email;
    modalPhone.textContent = entry.phone;
    modalLocation.textContent = entry.location;
    modalSummary.textContent = entry.summary;

    modalExperience.innerHTML = entry.experience.map((item) => `
        <div class="rounded-3xl bg-slate-50 p-4">
            <h5 class="font-semibold text-slate-900">${item.role} — ${item.company}</h5>
            <p class="text-sm text-slate-500">${item.duration}</p>
            <p class="mt-2 text-slate-700">${item.description}</p>
        </div>
    `).join('');

    modalEducation.innerHTML = entry.education.map((item) => `
        <div class="rounded-3xl bg-slate-50 p-4">
            <h5 class="font-semibold text-slate-900">${item.degree}</h5>
            <p class="text-sm text-slate-500">${item.institution}</p>
            <p class="text-sm text-slate-500">${item.year}</p>
        </div>
    `).join('');

    modalSkills.innerHTML = entry.skills.map((skill) => `
        <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-700">${skill}</span>
    `).join('');

    modalProjects.innerHTML = entry.projects.map((project) => `
        <div class="rounded-3xl bg-slate-50 p-4">
            <h5 class="font-semibold text-slate-900">${project.title}</h5>
            <p class="text-slate-700">${project.description}</p>
        </div>
    `).join('');

    modalOverlay.classList.remove('hidden');
}

function closeModal() {
    modalOverlay?.classList.add('hidden');
}

window.openCvModal = openCvModal;

