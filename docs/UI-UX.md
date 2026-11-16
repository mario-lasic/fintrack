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

Use a light canvas, white surfaces, dark text, and one teal accent for primary actions. Keep the layout quiet with spacing, borders, and clear type hierarchy. Give amounts and the current financial context visual priority.

### Starter Components

| FinTrack use | Starter components and adaptation |
|---|---|
| Application shell | Use `AppSidebarLayout` and `AppSidebar` on wide screens. Use the same labeled navigation links in a bottom bar on phones. Adapt the starter's narrow sidebar, which currently opens a sheet, and use the same breakpoint for both navigation layouts. Mark the current page in each layout. |
| Profile and financial context | Keep the starter profile menu. Use a labeled `DropdownMenu` with radio items for the active household and view. Use `Select` with a visible label for account, category, and other form choices. |
| Actions and summaries | Reuse `Button` for actions and `Card` for overview metrics. Use a primary button for the page's main action, a quieter style for secondary actions, and destructive styling on the final reversal confirmation. Let long action labels wrap on small screens. |
| Form fields and errors | Reuse `Label`, `Input`, and `InputError`. Associate the field with its label and error, and expose its invalid state. Use `Alert` for a page-level error or status that should remain visible. |
| Confirmation and feedback | Reuse `Dialog` with a visible title and description. Use `Skeleton` for loading content and `Spinner` alongside a descriptive button label while saving. |

### Colors and Typography

Apply the palette through the existing semantic tokens in `resources/css/app.css`. These values specify the light appearance. Keep the starter's Light, Dark, and System preference and use its theme variables for dark mode. Check contrast in both appearances when the financial screens are implemented.

| Role | Starter token or use | Light color |
|---|---|---|
| Canvas | `--background` | `#F6F8FA` |
| Surface and primary button text | `--card`, `--popover`, `--primary-foreground` | `#FFFFFF` |
| Primary text | `--foreground`, card and popover text | `#17212B` |
| Secondary text | `--muted-foreground` | `#475569` |
| Primary action and focus indicator | `--primary`, `--ring` | `#0F766E` |
| Primary hover | Button hover background | `#0B615B` |
| Control outlines | `--input` | `#64748B` |
| Decorative dividers | `--border` | `#E2E8F0` |
| Error text and reversal confirmation | `--destructive` | `#B42318` |

Use Inter with a system sans-serif fallback. Use 16 px for body text, 14 px for secondary text, 24 px for page titles, and 28–32 px for summary amounts. Use tabular numerals and right-align comparable amounts. Use a 4 px spacing scale with 8–12 px between related controls, 16 px inside mobile cards, and 24 px between sections.

### Forms and Keyboard

- Use persistent field labels and place help and errors beside the field. Keep the save destination visible on financial forms and in the primary button label, for example `Save to Personal`. After an invalid submission, retain valid input and move focus to the first invalid field or a linked error summary.
- Follow the visual reading order with Tab and Shift+Tab. The context menu opens with Enter or Space, supports arrow navigation, and closes with Escape. Return focus to its trigger when dismissed. In a confirmation dialog, keep focus inside, initially focus `Cancel` for a reversal, and restore focus when the dialog closes. Announce form errors and save results to assistive technology.
- Use a solid 2 px focus outline with a 2 px offset. Keep focused controls visible above the mobile bottom bar. Pair status colors with words or signs. Use underlines for inline links where the color alone would be the only cue.

### Money and Dates

Display EUR using Croatian formatting, for example `2.840,00 €`, `+2.100,00 €`, and `−48,20 €`. Use a visible `EUR` suffix and a decimal keyboard for amount entry. Show a local example such as `48,20`, keep the entered string while the user edits, and display an error for unsupported formats or excess precision. MONEY-01 defines the supported formats and range. Use the configured business date for transaction dates and period boundaries. Wireframe labels are in English for documentation.

### Responsive Layout

- Below 768 CSS px, use labeled bottom navigation and one content column. Keep the financial context and main action near the top. Allow the household name to wrap and reserve space for the bottom bar and device safe area.
- At 768 CSS px and above, use the sidebar. Let summary cards sit side by side only when their labels and amounts fit. Present transactions as a compact list on phones and show additional columns on wide screens.
- At 320 CSS px, let long transaction names wrap and place the amount on its own line when needed. Keep its currency and sign visible. The narrow layout must remain usable without horizontal page scrolling. Use at least 44 × 44 CSS px for touch targets on mobile.

The narrow transaction form keeps the invalid amount, its error, and the save destination visible.

```text
[Shared · Novak Household and
 Renovation Fund              ▾]

Add expense

Amount
[48,205                   EUR]
Enter up to two decimal places.

Account
[Checking                  ▾]
Category
[Groceries                 ▾]
Date
[16.11.2026.                  ]
[Save to Novak Household and
 Renovation Fund]

Overview  Transactions  Accounts
```

### UX-02 Design Review

- **Contrast.** Calculated light color pairs against the thresholds for normal text and controls. Primary text on white is 16.29:1, secondary text on the canvas is 7.12:1, white on the primary button is 5.47:1 (7.30:1 on hover), and error text on white is 6.57:1. The input outline and solid focus outline against the canvas are 4.47:1 and 5.14:1. At 50% opacity, the teal focus color over white is about 2.15:1, so the design specifies a solid outline. See [WCAG text contrast](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) and [WCAG non-text contrast](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html).
- **Keyboard walkthrough.** Reviewed the starter menu, select, and dialog components and traced opening and dismissing the context picker, submitting an invalid amount, and canceling a reversal. The expected focus destinations are the picker trigger, the input with the validation error, and the reversal trigger, respectively.
- **Narrow-screen walkthrough.** Reviewed the overview wireframe and the form layout at the 320 CSS px design target. With a long household name and an amount such as `−1.234.567,89 €`, the single column leaves room for the name to wrap and the full amount to move below its label. The form allows the destination on the save button to wrap and keeps the action clear of the bottom bar. See [WCAG reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html).
- **Browser verification.** Check the rendered component sizes, focus order and visibility, error announcements, color pairs in both appearances, and reflow at 320 CSS px when the financial screens are implemented.

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
