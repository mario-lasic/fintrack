# FinTrack UI/UX Design

This document records the agreed design direction and the initial MVP wireframes. Review the navigation, visual foundations, and workflows under [UX-01 through UX-03](../../../Documents/ChatGPT/FinTrack%20—%20Hybrid%20Financial%20OS/docs/ROADMAP.md#epic-uiux-planning). [PROGRESS.md](../../../Documents/ChatGPT/FinTrack%20—%20Hybrid%20Financial%20OS/docs/PROGRESS.md) records task and commit status.

## Navigation and Financial Context

Use one application shell with **Overview**, **Transactions**, and **Accounts** as the primary destinations. Place profile and settings in the profile menu and household management with the household controls. Keep the current destination and financial context visible on every financial screen, including forms.

| View | Label | Visible data |
|---|---|---|
| Personal | `Personal` | The signed-in user's private accounts in the active household and the activity they are authorized to see for those accounts. |
| Shared | `Shared · Household name` | Shared accounts and authorized activity for the named household. Household owners see the same private-account boundary as other members. |

Show the active household name alongside `Personal` so users can distinguish their private views across households. The picker lets users select a household they belong to and either of its two views. Balances, counts, and transaction lists use the selected household and view. Account options follow the current view for income and expense entry. The transfer form can offer writable accounts from either view within the active household. The server authorizes each request and sends only the fields the current user may see.

- **First sign-in.** Open `Personal` in the active household. If the user has no active household, select their only household or ask them to choose among their memberships. A user without a household sees how to create one or accept an invitation. Once the context is available, the empty personal overview offers `Create private account` as its primary action.
- **Switch context.** Keep the current destination when it is a list or the overview. Refresh its scope-sensitive content and clear account or category filters that no longer apply. A switch from a record detail opens the corresponding list in the new context. Prompt users to stay or discard changes before leaving an unsaved form, and keep the form's original destination fixed until they leave it.
- **Access denied.** For an inaccessible record, show a generic unavailable state without identifying the restricted resource and offer a return to the current authorized overview. If membership has been revoked, clear the affected scope and offer another authorized household or the setup screen when no household remains.

Account actions depend on the user's role and the account's visibility in the [household access matrix](ARCHITECTURE.md#arc-02--household-roles-and-privacy). Active members can create their own private accounts. Household owners can also create shared accounts.

## Visual Foundations

Use a light canvas, white surfaces, dark text, and one teal accent for primary actions. Keep the layout quiet with spacing, borders, and clear type hierarchy. Give amounts and the current financial context visual priority. Reuse the Laravel Vue starter components for navigation, menus, buttons, inputs, and confirmation dialogs, then apply the shared visual tokens.

| Role | Token |
|---|---|
| Canvas | `#F6F8FA` |
| Surface and primary button text | `#FFFFFF` |
| Primary text | `#17212B` |
| Secondary text | `#475569` |
| Primary action and focus indicator | `#0F766E` |
| Control outlines | `#64748B` |
| Decorative dividers | `#E2E8F0` |
| Error text | `#B42318` |

- **Type.** Use Inter with a system sans-serif fallback. Start with 16 px body text, 14 px secondary text, 24 px page titles, and 28–32 px summary amounts. Use tabular numerals and right-align comparable amounts.
- **Spacing.** Use a 4 px base with 8–12 px between related controls, 16 px inside mobile cards, and 24 px between sections. Give each page one prominent primary action.
- **Forms.** Give controls persistent labels and place help or errors beside the relevant field. Keep the save destination visible on financial forms and in the primary button label, for example `Save to Personal`. After an invalid submission, retain valid input and move focus to the first invalid field or a linked error summary.
- **Money and dates.** Display EUR using Croatian formatting, for example `2.840,00 €`, `+2.100,00 €`, and `−48,20 €`. Use a visible `EUR` label on amount inputs and a decimal comma hint where needed. Use the configured business date for transaction dates and period boundaries. Wireframe labels are in English for documentation.
- **Accessibility.** Use a visible focus outline with an offset, a logical tab order, and keyboard-operable menus and dialogs. Return focus to the invoking control when a dialog closes. Pair status colors with words or signs, and announce form errors and save results to assistive technology.
- **Responsive layout.** Use a sidebar on wide screens and labeled bottom navigation on phones. Keep the context picker at the top and the primary action near the page title. Stack summary metrics and form fields on narrow screens. Present transactions as rows with a name, date, and amount on phones, with other fields in the detail view. Leave room for the bottom navigation and use 44 px as the touch target design size.

## Overview Wireframes

```text
FinTrack              Personal ▾ · Novak household             Profile

Overview              Overview                        + Add transaction
Transactions
Accounts              Total balance      Income          Spending
                      2.840,00 €         2.100,00 €      860,00 €
                      Current            This month      This month

                      Recent transactions                       View all
                      Groceries                                 −48,20 €
                      Salary                                 +2.100,00 €
```

```text
FinTrack                        Profile
[Shared · Novak household        ▾]

Overview
[+ Add transaction]

Total balance               840,00 €
Income · This month         900,00 €
Spending · This month       560,00 €

Recent transactions         View all
Electricity                 −62,40 €
12.11.2026.

Overview     Transactions    Accounts
```

The balance is current within the selected view. Income and spending use the selected reporting period, initially `This month`. Exclude transfers and opening balances from income and spending. Recent transactions may show authorized transfers and other entries with their type clearly labeled. A confirmed zero balance displays `0,00 €`. Missing or failed totals use their own states so they cannot be mistaken for zero.

## MVP Workflow Drafts

Each form uses the same sequence of title, financial context, labeled fields, and primary action. The first walkthrough is **Overview → Add transaction → Overview**.

| Screen | Content and interaction |
|---|---|
| Accounts | Show account name, type, and balance in the active view. Account detail shows `Private` or `Shared` and offers the authorized management actions. The create form identifies the intended visibility before saving. Show archived accounts separately when requested and retain their history. |
| Transactions | Show recent activity with search and date, account, category, and type filters. Each row opens an authorized detail view. Distinguish an empty history from a search with no matches. |
| Add transaction | Choose `Expense` or `Income`, then enter amount, account, category, date, and optional note. Restrict the account picker to writable, active accounts in the current view. `Save to Personal` or `Save to [household name]` confirms the destination. On success, return to the originating screen in the same context and refresh its data. |
| Transfer | Show `From account`, `To account`, amount, and date. Label each account's visibility and show the direction before `Confirm transfer`. Both accounts must be writable by the actor and belong to the same household. In each view's activity list, show the direction and signed amount for that view's account. Details can show both accounts when the viewer has access to both. If access is limited to one side, show its direction, amount, and date using a generic label such as `Transfer from private account`. Omit the hidden account's name, identifier, and balance and any note that could identify it from the data sent to the browser, including detail and search results. |
| Reverse transaction | From an authorized detail view, show the original entry, reversal date, reason, and `Confirm reversal`. Offer `Reverse transfer` only when the user can post to both accounts. After success, show the original as `Reversed` and link to the reversal when the viewer is authorized to see it. A financial correction uses the reverse-and-replace flow in the roadmap. |

| State | Expected behavior |
|---|---|
| Empty | Explain the relevant gap. Use `Create private account` in an empty personal view and `Create shared account` for household owners in an empty shared view. Other members see `No shared accounts yet. A household owner can create one.` Use `Add transaction` when the view has accounts but no activity, and `Clear filters` when the current query yields no matches. |
| Loading | Keep the shell in place and show loading placeholders for the target scope. Clear old financial data from the visible content when switching context. During writes, show an in-progress label and block repeated presses. |
| Validation or error | Show field errors next to the fields. For a failed read, show `Retry` in the current context. If a posting outcome is unknown, preserve the form and retry the same attempt with the same idempotency key so one submission can result in only one posting. |
| Success | Show a short confirmation and the updated record or list in the same context. Refresh affected balances and recent activity after the server confirms the write. |

### Mixed-Visibility Transfer Example

Ana transfers `50,00 €` from her private checking account to a shared account in the same household. Her personal overview shows an outflow of `−50,00 €` and her shared overview shows an inflow of `+50,00 €`. She can see both accounts in the transfer detail. Marko, a household owner, sees the shared inflow and date labeled `Transfer from private account`. His view omits Ana's private account details, identifying notes, and the reversal action. The transfer changes both account balances and leaves the monthly income and spending totals unchanged.

## Review Before Implementation

- **UX-01:** Walk through first sign-in with and without a household, switching between personal and shared views in two households, and opening an inaccessible record. Check that totals, filters, and form destinations reflect the visible scope.
- **UX-02:** Check the applied color pairs, keyboard focus and order, amount input and formatting, and layouts at narrow widths and at browser zoom. Confirm that summary amounts and the context picker remain legible with long values and household names.
- **UX-03:** Walk through account creation, income and expense entry, filtered results, transfer, and reversal. Include validation errors, repeat submission, and a private-to-shared transfer viewed by its actor and by another household member. Confirm period and reversal semantics with `ARC-04`.
