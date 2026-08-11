var e=[{id:1,name:`Avery Carter`,email:`avery.carter@example.com`,phone:`+1 555 987 6543`,location:`New York, USA`,title:`Civil Engineer`,summary:`Driven civil engineer with experience designing safe and sustainable infrastructure projects.`,experience:[{company:`Metro Construction`,role:`Project Engineer`,duration:`2022 - Present`,description:`Managed site inspections and supported structural design for urban bridges.`},{company:`Urban Build Co.`,role:`Junior Civil Engineer`,duration:`2019 - 2022`,description:`Assisted with roadway design and performed hydrology analysis for residential developments.`}],education:[{institution:`State University`,degree:`B.Sc. Civil Engineering`,year:`2019`}],skills:[`AutoCAD`,`Revit`,`Project Scheduling`,`Roadway Design`],projects:[{title:`Park Avenue Bridge Retrofit`,description:`Coordinated a successful rehabilitation plan for a high-traffic urban bridge.`}]}],t=document.getElementById(`uploadForm`),n=document.getElementById(`fullName`),r=document.getElementById(`jobTitle`),i=document.getElementById(`email`),a=document.getElementById(`phone`),o=document.getElementById(`cvFile`),s=document.getElementById(`uploadStatus`),c=document.getElementById(`previewContainer`),l=document.getElementById(`searchInput`),u=document.getElementById(`searchResults`),d=document.getElementById(`noResults`),f=document.getElementById(`modalOverlay`),p=document.getElementById(`modalName`),m=document.getElementById(`modalTitle`),h=document.getElementById(`modalEmail`),g=document.getElementById(`modalPhone`),_=document.getElementById(`modalLocation`),v=document.getElementById(`modalSummary`),y=document.getElementById(`modalExperience`),b=document.getElementById(`modalEducation`),x=document.getElementById(`modalSkills`),S=document.getElementById(`modalProjects`),C=document.getElementById(`modalClose`),w=document.getElementById(`fileName`),T=document.getElementById(`search-form`),E=document.getElementById(`searchPreview`),D=document.getElementById(`searchTerm`),O=document.getElementById(`candidateTableBody`),k=2;F(),P(),t?.addEventListener(`submit`,async s=>{if(s.preventDefault(),!o?.files.length){j(`Please choose a PDF file before uploading.`,!1);return}j(`Scanning CV... Please wait.`,!0,!0),await A(2e3);let c=M({name:n.value.trim(),title:r.value.trim(),email:i.value.trim(),phone:a.value.trim()});e.push(c),N(c),j(`CV Uploaded & Scanned Successfully`,!0,!1),P(l.value.trim()),F(),t.reset(),w.textContent=`PDF, DOC, DOCX • Max file size: 10MB`}),o?.addEventListener(`change`,()=>{let e=o.files[0];w.textContent=e?e.name:`PDF, DOC, DOCX • Max file size: 10MB`}),l?.addEventListener(`input`,()=>{P(l.value.trim())}),T?.addEventListener(`submit`,e=>{e.preventDefault();let t=l.value.trim();E?.classList.toggle(`hidden`,!t),t&&(D.textContent=t),P(t)}),C?.addEventListener(`click`,()=>{L()}),f?.addEventListener(`click`,e=>{e.target===f&&L()});function A(e){return new Promise(t=>setTimeout(t,e))}function j(e,t=!0,n=!1){s&&(s.classList.remove(`hidden`),s.textContent=`${n?`Processing`:t?`Success`:`Error`}: ${e}`,s.className=`rounded-3xl border px-4 py-3 text-sm ${t?`border-slate-200 bg-slate-50 text-slate-800`:`border-rose-200 bg-rose-50 text-rose-700`}`)}function M({name:e,title:t,email:n,phone:r}){let i=t||`Civil Engineer`,a=e||`Jordan Blake`,o=`Experienced ${i} with a strong track record of delivering infrastructure and construction projects on time and on budget.`;return{id:k++,name:a,email:n||`candidate@example.com`,phone:r||`+1 555 321 9876`,location:`San Francisco, CA`,title:i,summary:o,experience:[{company:`Riverfront Planning Group`,role:`${i}`,duration:`2024 - Present`,description:`Led feasibility studies, coordinated civil design packages, and collaborated with stakeholders across municipal teams.`},{company:`Pacific Engineering Partners`,role:`Site Engineer`,duration:`2021 - 2024`,description:`Supported the construction of highway expansions, managed permit submissions, and monitored quality compliance.`}],education:[{institution:`Technical Institute`,degree:`B.Eng. Civil Engineering`,year:`2021`},{institution:`Professional Development Academy`,degree:`Project Management Certification`,year:`2023`}],skills:[`Site Analysis`,`Structural Modeling`,`Drainage Design`,`Contract Coordination`,`AutoCAD`],projects:[{title:`Downtown Traffic Relief Plan`,description:`Designed a traffic optimization scheme for an urban corridor with sustainability improvements.`},{title:`Residential Stormwater System`,description:`Delivered a low-impact drainage solution for a multi-block development.`}]}}function N(e){let t=document.getElementById(`previewName`),n=document.getElementById(`previewTitle`),r=document.getElementById(`previewSummary`),i=document.getElementById(`previewExperience`),a=document.getElementById(`previewEducation`),o=document.getElementById(`previewSkills`),s=document.getElementById(`previewProjects`);!c||!t||!n||(c.classList.remove(`hidden`),t.textContent=e.name,n.textContent=e.title,r.textContent=e.summary,i.innerHTML=e.experience.map(e=>`<li><strong>${e.role}</strong> at ${e.company} — ${e.duration}<br>${e.description}</li>`).join(``),a.innerHTML=e.education.map(e=>`<li>${e.degree}, ${e.institution} (${e.year})</li>`).join(``),o.innerHTML=e.skills.map(e=>`<li>${e}</li>`).join(``),s.innerHTML=e.projects.map(e=>`<li><strong>${e.title}</strong> — ${e.description}</li>`).join(``))}function P(t=``){if(!u||!d)return;let n=t.toLowerCase(),r=e.filter(e=>e.title.toLowerCase().includes(n));if(u.innerHTML=``,!r.length){d.classList.remove(`hidden`);return}d.classList.add(`hidden`),r.forEach(e=>{let t=e.experience.length,n=document.createElement(`div`);n.className=`rounded-[26px] border border-slate-200 bg-white p-6 shadow-sm`,n.innerHTML=`
            <div class="space-y-3">
                <h3 class="text-xl font-semibold text-slate-900">${e.name}</h3>
                <p class="text-sm text-slate-500"><strong>${e.title}</strong></p>
                <p class="text-sm text-slate-600">Total Experience: ${t} roles</p>
                <p class="text-sm text-slate-600">${e.summary}</p>
            </div>
            <div class="mt-6 text-right">
                <button type="button" class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700" data-id="${e.id}">View Full CV</button>
            </div>
        `,n.querySelector(`button`)?.addEventListener(`click`,()=>I(e.id)),u.appendChild(n)})}function F(){O&&(O.innerHTML=e.map(e=>`
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-700 font-semibold">${e.name.split(` `).map(e=>e[0]).join(``).slice(0,2)}</div>
                    <div>
                        <p class="font-semibold text-slate-900">${e.name}</p>
                        <p class="text-sm text-slate-500">${e.title}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-4 text-sm text-slate-600">
                <p>${e.email}</p>
                <p>${e.phone}</p>
            </td>
            <td class="px-4 py-4 text-slate-600">${e.title}</td>
            <td class="px-4 py-4 text-slate-600">${e.experience.length} Years</td>
            <td class="px-4 py-4 text-slate-600">${e.skills.map(e=>`<span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">${e}</span>`).join(` `)}</td>
            <td class="px-4 py-4 text-slate-600">${e.education[0]?.degree||``}</td>
            <td class="px-4 py-4 text-slate-600">2 May 2024</td>
            <td class="px-4 py-4">
                <button type="button" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" onclick="openCvModal(${e.id})">View CV</button>
            </td>
        </tr>
    `).join(``))}function I(t){let n=e.find(e=>e.id===t);!n||!f||(p.textContent=n.name,m.textContent=n.title,h.textContent=n.email,g.textContent=n.phone,_.textContent=n.location,v.textContent=n.summary,y.innerHTML=n.experience.map(e=>`
        <div class="rounded-3xl bg-slate-50 p-4">
            <h5 class="font-semibold text-slate-900">${e.role} — ${e.company}</h5>
            <p class="text-sm text-slate-500">${e.duration}</p>
            <p class="mt-2 text-slate-700">${e.description}</p>
        </div>
    `).join(``),b.innerHTML=n.education.map(e=>`
        <div class="rounded-3xl bg-slate-50 p-4">
            <h5 class="font-semibold text-slate-900">${e.degree}</h5>
            <p class="text-sm text-slate-500">${e.institution}</p>
            <p class="text-sm text-slate-500">${e.year}</p>
        </div>
    `).join(``),x.innerHTML=n.skills.map(e=>`
        <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-700">${e}</span>
    `).join(``),S.innerHTML=n.projects.map(e=>`
        <div class="rounded-3xl bg-slate-50 p-4">
            <h5 class="font-semibold text-slate-900">${e.title}</h5>
            <p class="text-slate-700">${e.description}</p>
        </div>
    `).join(``),f.classList.remove(`hidden`))}function L(){f?.classList.add(`hidden`)}window.openCvModal=I;