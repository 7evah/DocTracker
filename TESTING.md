# Testing DocFlow — role-by-role scenarios

This walks every role through the app end to end, starting from a genuinely
empty install. Follow the sections **in order** — each one builds the data
the next role needs (Engineer needs a project before it can upload a
document into it, a Reviewer needs something assigned before there is
anything to review, and so on). By the end you will have exercised every
module in the sidebar and every major workflow in the spec.

## Reset to a clean slate

```bash
php artisan migrate:fresh --seed
php artisan queue:work    # in a second terminal — notifications are queued (§26)
```

This seeds **only** roles, permissions, the 10 reference disciplines, and
the eight accounts below (§55). No projects, documents, reviews, approvals
or tasks exist yet — you create all of it in the walkthrough. If you'd
rather start from a fully-populated showcase instead of building it up by
hand, see [*Populating a full demo dataset instead*](#populating-a-full-demo-dataset-instead)
at the end.

## Accounts

All passwords are `password`.

| Role | Email | Name |
|---|---|---|
| Administrator | `adminjesa@yopmail.com` | Hamza El Badaoui |
| Project Manager | `chef.projet@yopmail.com` | Nadia Benchekroun |
| Engineer | `ingenieur1@yopmail.com` | Youssef Amrani |
| Engineer | `ingenieur2@yopmail.com` | Salma Tazi |
| Reviewer | `verificateur1@yopmail.com` | Karim Oulhaj |
| Reviewer | `verificateur2@yopmail.com` | Imane Rachidi |
| Approver | `approbateur@yopmail.com` | Rachid El Malki |
| Viewer | `lecteurjesa@yopmail.com` | Leila Bouzid |

---

## 1. Administrator — bootstrap the installation

Log in as **adminjesa@yopmail.com**. Nothing else can be meaningfully tested
until this section is done: it creates the approval circuit every document
will eventually pass through.

- [ ] **Dashboard** (`/dashboard`) — every stat reads 0; every section
  (Recent Documents, Pending Reviews, Upcoming Deadlines, Recent Activity)
  shows its empty-state icon and message rather than a blank space.
- [ ] **Administration → Circuits d'approbation** (`/admin/workflows`) —
  click **Nouveau circuit**. Create the standard three-step circuit from
  §8:
  1. Step 1, role **Vérificateur**, label "Vérification technique", 5 days
  2. Step 2, role **Chef de projet**, label "Validation chef de projet", 3 days
  3. Step 3, role **Approbateur**, label "Approbation finale", 3 days

  Tick **Circuit par défaut** (leave **Actif** as-is — it's already checked
  by default, so clicking it would turn it *off*), leave **Projet** empty
  (global), save.
  *Without this step, documents will jump straight from review to Approved
  with no signature chain — do this first.*
- [ ] **Administration → Disciplines** (`/admin/disciplines`) — the 10
  seeded disciplines (CV, ST, ME, PI, EL, IN, PR, AR, HS, XX) are listed.
  Create one more (e.g. code `TP`, name "Travaux publics"), confirm the
  code is upper-cased automatically, edit its description, then delete it
  again (works — nothing references it yet).
- [ ] **Administration → Rôles et permissions** (`/admin/roles`) — the
  matrix opens with permissions down the side, roles across the top. The
  **Administrateur** column is entirely disabled (checked and locked — see
  the note above the table). Toggle one permission for **Lecteur**, save,
  reload the page and confirm it stuck; toggle it back off.
- [ ] **Administration → Utilisateurs** (`/admin/users`) — all 8 seeded
  accounts are listed with correct roles and an *Actif* badge. Filter by
  role, filter by status, clear filters.
  - [ ] Try to deactivate **your own account** from the row menu → refused
    with a toast explaining why, status unchanged.
  - [ ] Open your own **Modifier** dialog, untick **Administrateur** in the
    role list, save → rejected with a field error; you cannot demote
    yourself out of the only admin account.
- [ ] **Administration → Paramètres** (`/admin/settings`) — change the
  default review turnaround to a different number, save, reload, confirm
  the new value is still there. Read the system info panel on the right.

## 2. Project Manager — create a project

Log out, log in as **chef.projet@yopmail.com**.

- [ ] **Projects** (`/projects`) — empty. Click **Nouveau projet**.
  Create:
  - Code `OCP-GA-2026`, name "OCP Green Ammonia Project", client "OCP
    Group", location "Jorf Lasfar", manager = yourself, status *En cours*,
    start/end dates a year apart.
- [ ] Open the project. Overview tab shows 0 documents, 0% progress. Try
  the **Tâches** tab, click **Nouvelle tâche**, create one (e.g. "Réunion
  de lancement", assign to yourself, due next week) — confirm it appears
  and the project/document fields were pre-filled and locked.
- [ ] Click through the other four tabs — **Documents**, **Revues**,
  **Approbations**, **Activité**. All four are empty except *Activité*,
  which already shows "a créé le projet". Each empty state should explain
  what will appear there, not just say "nothing".
- [ ] Dashboard now shows **1** under Projects.

*Leave this account signed in conceptually — you'll return as PM to assign
reviewers in section 4.*

## 3. Engineer — upload a document

Log out, log in as **ingenieur1@yopmail.com**.

- [ ] **Documents → Téléverser un document** (`/documents/create`). Select
  the project you just created, discipline **PI — Piping**, watch the
  document number field pre-fill with `PI-`; complete it to `PI-1023`.
  Title "Plan d'implantation tuyauterie". Attach any PDF (or any file
  under the allowed types/size — invalid ones should show a clear
  validation error before you try a real one). Revision `A`. Upload.
- [ ] You land on the document page, status **Brouillon**. Download the
  file you just uploaded — confirm the original filename comes back
  correctly.
- [ ] Click **Soumettre pour revue**. Status changes to *En revue*, and
  the **project manager** gets a notification saying the revision is
  waiting for a reviewer to be assigned. Submitting no longer happens
  silently — check chef.projet's bell after this step.
  > Assigning the reviewer is still a separate act, done by the Project
  > Manager in section 4. What submitting does is tell them there is
  > something to assign.

## 4. Project Manager — assign a reviewer

Log back in as **chef.projet@yopmail.com**, open `PI-1023`.

- [ ] Bell shows the submission notification from section 3. Open the
  document from it.
- [ ] Click **Affecter pour revue**. Tick **Karim Oulhaj**, priority
  *Haute*, confirm the deadline auto-fills. Leave **Portée de
  l'affectation** on *Cette révision uniquement* for now, then save.
  Status is (or remains) *En revue*; the **Revues** tab now lists one
  entry against Karim.
- [ ] Check the header bell — no badge yet (you didn't assign yourself).

## 5. Reviewer — review, comment, request a revision

Log out, log in as **verificateur1@yopmail.com** (Karim Oulhaj).

- [ ] Header bell shows **1** unread. Open it, click the notification —
  it marks read and lands you on the review.
- [ ] `/reviews` — the review is in your queue, status now *En cours*
  (opening it moved it there automatically).
- [ ] On the review page: the PDF preview loads inline (or shows the
  download fallback if your file wasn't a PDF). Add a comment. Click
  **Demander une révision** — the confirmation dialog requires a summary;
  try submitting empty first (blocked), then fill it in and confirm.
- [ ] Document status is now **Révision requise**. Its Comments tab shows
  your remark.

## 6. Engineer — upload the revision

Log back in as **ingenieur1@yopmail.com**, open `PI-1023`.

- [ ] **Historique des révisions** tab shows revision A. Click **Nouvelle
  révision**, upload a second file, add version notes referencing the
  comment. Confirm revision **B** appears, A is untouched and still
  downloadable, and the document status returned to **Brouillon**.
- [ ] Submit for review again (as in step 3). Because Karim was assigned
  for *this revision only*, revision B goes in with **no reviewer**, and
  chef.projet gets the "waiting for a reviewer" notification again.

### The standing-reviewer alternative

That repetition is what **Portée de l'affectation** exists to avoid. Try
it once so you have seen both halves:

- [ ] As **chef.projet**, assign Karim to revision B, this time choosing
  *Toutes les révisions à venir*.
- [ ] As **ingenieur1**, upload a revision C and submit it.
- [ ] Karim is assigned to revision C automatically — check his `/reviews`
  queue without anyone having picked him again. chef.projet's
  notification this time says the usual reviewers were kept, rather than
  asking for an assignment.
- [ ] Assigning somebody else explicitly to a revision still wins: an
  explicit choice is never overwritten by the standing one.

## 7. Project Manager → Reviewer — approve the review

- [ ] As **chef.projet**, open `PI-1023`, click **Modifier les
  vérificateurs** (the button relabels once a review already exists),
  reassign Karim to revision B.
- [ ] As **verificateur1**, open the review, click **Approuver** (no
  summary required for an approval, unlike a rejection). Document status
  stays **En revue** — approving the *only* review clears the review
  stage and hands off to the approval circuit, but does not finish the
  document outright.
- [ ] Open the document's **Approbations** tab: the stepper now shows
  step 1 (Vérificateur) **active** (●), steps 2 and 3 waiting (○).
  > Reviewing a document and signing an approval step are deliberately
  > separate actions, even when — as here — the same person ends up doing
  > both because the circuit's first step happens to be assigned to the
  > Vérificateur role. §7 (technical review) and §8 (formal approval) are
  > distinct concerns with their own records, so approving the review does
  > not silently also sign step 1.

## 8. Reviewer — sign step 1 of the approval circuit

Still as **verificateur1@yopmail.com**:

- [ ] On the same document's **Approbations** tab, click **Approuver** on
  the now-active step 1, confirm. It turns ✓, step 2 (Chef de projet)
  activates, and **chef.projet@yopmail.com** gets a notification.

## 9. Project Manager — sign step 2

- [ ] Log in as **chef.projet@yopmail.com**, open the document's
  **Approbations** tab, **Approuver**, confirm. Step 2 turns ✓, step 3
  activates, and **approbateur@yopmail.com** gets a notification.

## 10. Approver — final signature

Log in as **approbateur@yopmail.com**.

- [ ] Bell shows 1 unread. Open `/approvals` — the step is in your queue.
- [ ] Open the document, **Approbations** tab, **Approuver**, confirm.
  Document status becomes **Approuvé**. All three steps show ✓.
- [ ] `ingenieur1@yopmail.com` (the document's author) should have
  received a "document approved" notification — log in as them to check
  the bell.
- [ ] Go back to the **project** (`/projects`, open `OCP-GA-2026`) and
  re-check the four tabs now that there is history behind them:
  - **Documents** lists `PI-1023` at revision B, status *Approuvé*.
  - **Revues** lists both passes on it — revision A *Révision demandée*,
    revision B *Approuvé*.
  - **Approbations** lists all three signed steps with their signers.
  - **Activité** reads as a full project timeline, oldest at the bottom:
    "a créé le projet" through to "a approuvé le document".

  Every row links back out to the document or review it belongs to.

## 11. Try a rejection path too

Repeat steps 3–5 with **ingenieur2 / verificateur2** on a second document
(different discipline, e.g. `CV-0102`), but this time click **Rejeter**
instead of requesting a revision — confirm the document status becomes
**Rejeté** and, if it had already entered the approval circuit, remaining
steps show as **Ignorée** rather than sitting pending forever.

## 12. Viewer — confirm read-only access

Log in as **lecteurjesa@yopmail.com**.

- [ ] Projects, Documents, Reviews, Approvals, Tasks, Reports are all
  visible and readable.
- [ ] Confirm there is no **Nouveau projet** / **Téléverser un document**
  / **Nouvelle tâche** button anywhere, and that navigating directly to
  `/projects/create` or `/documents/create` returns a 403, not a form.
- [ ] `/admin/*` routes all 403.

## 13. E-mail: notifications and the forgotten password

The demo accounts all use `@yopmail.com`, a throwaway inbox needing no signup:
open <https://yopmail.com>, type the address (without the domain), read what
arrived. Nothing sends unless `MAIL_MAILER=smtp` is configured with a
provider's credentials and `php artisan queue:work` is running — see
[*Sending real e-mail*](README.md#sending-real-e-mail). With `MAIL_MAILER=log`
the messages land in `storage/logs/laravel.log` instead, which is enough to
check wording and layout.

- [ ] Trigger any notification from the walkthrough above (submitting a
  revision is the quickest). The mail should carry the JESA/DocFlow header,
  say in one line what happened, and offer a single button straight to the
  document — not a generic "log in and look around".
- [ ] Click that button and confirm it lands on the right page. If it 404s,
  `APP_URL` does not match the address you actually browse on.

### Forgotten password

- [ ] Log out. On the login screen click **Mot de passe oublié ?** and submit
  `ingenieur1@yopmail.com`.
- [ ] The confirmation is deliberately non-committal (*"Si un compte
  existe…"*) — submit a made-up address and confirm you get the same answer,
  so the form cannot be used to find out who has an account.
- [ ] Read the mail: it contains a **temporary password** in a boxed,
  monospaced panel, says it lasts 60 minutes and works once, and says the
  existing password still works.
- [ ] **Confirm that last part is true:** log in with `password` as normal. It
  should still work — requesting a reset must not lock anyone out, which is
  why the temporary password is stored beside the real one rather than
  replacing it.
- [ ] Now log in with the temporary password instead. You land on **Choisissez
  un nouveau mot de passe** and cannot leave: try navigating to `/documents`
  and confirm you are returned to it. Only **Déconnexion** gets you out.
- [ ] Set a new password. You are released to the dashboard.
- [ ] Log out and back in with the new password — and confirm the temporary
  one no longer works. It buys one sign-in, not a second standing credential.

## 14. Cross-cutting checks (any role with access)

- [ ] **Reports** (`/reports`, as `chef.projet` or `admin`) — cycle
  through all nine reports in the left rail. Confirm the bar chart appears
  for the three distribution reports and not the others. Set a project
  filter, confirm it narrows the table; switch to *Charge par
  utilisateur*, confirm the project filter greys out with an explanation
  rather than silently doing nothing.
  - [ ] Click **Exporter en Excel** — a real `.xlsx` downloads and opens.
  - [ ] Click **Exporter en PDF** — a real PDF downloads, header shows the
    JESA branding and the filters that were applied.
  - [ ] As **lecteurjesa@yopmail.com**: the export buttons are absent
    entirely (viewing ≠ exporting).
- [ ] **Notifications** (`/notifications`) — filter All/Unread/Read, mark
  one read, mark one unread, **Tout marquer comme lu**, delete one,
  **Supprimer les notifications lues**.
- [ ] **Mobile** — shrink the browser under ~640px. Sidebar collapses to a
  hamburger; document/task lists switch from table to stacked cards; no
  page ever scrolls horizontally (only wide tables/the reports table
  scroll within their own box).
- [ ] **Dark mode** — toggle it (profile menu / system setting) and skim
  a few pages; status badges should stay legible, not just re-tinted.
- [ ] **Activity / audit trail** — open any touched document's
  **Activité** tab: every action from sections 3–9 should appear in order,
  each with who did it and when.

---

## Populating a full demo dataset instead

The scenarios above build data by hand on purpose, so every module gets a
deliberate, observable exercise. If you'd rather skip the manual walkthrough
and jump straight to a fully-populated install — five projects, sixteen
documents with real multi-revision histories, reviews, approvals, tasks and
notifications already in place — the original showcase seeders are still in
the codebase and fully tested, just not run by default:

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=Database\\Seeders\\ProjectSeeder
php artisan db:seed --class=Database\\Seeders\\DocumentSeeder
php artisan db:seed --class=Database\\Seeders\\ReviewSeeder
php artisan db:seed --class=Database\\Seeders\\ApprovalSeeder
php artisan db:seed --class=Database\\Seeders\\TaskSeeder
php artisan db:seed --class=Database\\Seeders\\NotificationSeeder
```

Their coverage is asserted by `tests/Feature/SeededDataSmokeTest.php`.
