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

Current balance             840,00 €

Activity period       [This month ▾]
Income                      900,00 €
Spending                    560,00 €

Recent transactions         View all
Electricity                 −62,40 €
12.11.2026.

Overview     Transactions    Accounts
```

The current balance sums the posted balances of accounts in the active view, including any balances still held in archived accounts. Changing the activity period leaves the current balance in place. If archived accounts have a nonzero balance, show `Includes archived accounts` near the total.

Start with `This month` and offer `Last month` and `Custom dates`. Use inclusive business dates in `Europe/Zagreb` for activity. Income and spending are net of their own reversals in the selected period. Exclude transfers and opening balances from these metrics. Recent transactions use the same period and the same visibility rules as the Transactions list. `View all` opens that list with the period applied. A confirmed zero displays `0,00 €`. Use a loading placeholder or an explicit error for unavailable totals.

## MVP Financial Workflows

Use the visible household and view throughout each flow. Open substantial forms as pages, with one column on narrow screens. Use confirmation dialogs for archival and reversal. The [privacy rules](ARCHITECTURE.md#arc-02--household-roles-and-privacy) and [financial invariants](ARCHITECTURE.md#arc-04--financial-and-temporal-invariants) apply throughout.

### Accounts

```text
[Personal · Novak household ▾]
Accounts                      [Create private account]
[ ] Show archived accounts

Checking        Checking                    1.110,00 €
Savings         Savings                        50,00 €

Checking                       Private     Active
Current balance                             1.110,00 €
[View transactions]            [Edit name] [Archive account]
```

```text
Create account                  Personal · Novak household
Name                            [Checking             ]
Type                            [Checking           ▾ ]
Visibility                      Private
[Cancel]                        [Create private account]
```

- Keep the initial list to active accounts. `Show archived accounts` reveals a separate group with an `Archived` label. Archived details retain the balance and history. Posting controls require an eligible active account.
- Fix visibility at creation from the current view. Offer `Create shared account` to household owners in Shared. Account metadata forms show the name and type at creation and support renaming afterward. Display balances as read-only ledger values. Once opening balances are available, an eligible account offers a separate `Record opening balance` action.
- `Archive account` opens a dialog with the account name and the effect on future postings and retained history. On success, keep the detail visible, mark it `Archived`, and remove it from posting selectors.

### Browse Transactions

```text
[Personal · Novak household ▾]
Transactions                                  [Add transaction]
[Search] [Date range ▾] [Account ▾] [Category ▾] [Type ▾]
[Clear filters]

Business date     Description           Account          Amount
30.03.2026.       Transfer to savings   Checking        −50,00 €
28.03.2026.       Groceries             Checking        −40,00 €

[Previous]                                      Page 1   [Next]
```

Sort by business date newest first with stable pagination. Keep active filters visible and return to the filtered list when a user closes a detail. On phones, place secondary filters behind `Filters` and show their active count. The detail shows the authorized business date, type, account, amount, and category or note when applicable. Label a reversal or a reversed original in both the list and detail. Apply the same visibility rules to search matches, row contents, detail links, and totals.

### Record Income or an Expense

```text
Add transaction                         Personal · Novak household
[Expense] [Income] [Transfer]

Amount          [48,20 EUR   ]
Account         [Checking  ▾ ]
Category        [Groceries ▾ ]
Business date   [Today's date]
Note            [Optional    ]

[Cancel]                       [Save to Personal]
```

Open with `Expense` selected unless the entry point specifies another type. Show categories for the selected type and clear a previous category when it becomes invalid. Offer only writable active accounts in the current view. Preselect a writable account when the entry starts from its detail. Use today's business date by default, allow earlier dates, and show a field error for future dates. Preserve entered values on validation errors. For Shared, show `Save to [household name]`. On success, return to the originating screen in the same context, refresh its data, and offer `View transaction`. If existing filters exclude the new row, preserve those filters and keep the detail link available.

### Transfer Between Accounts

```text
Transfer                                  Novak household

From account    [Checking · Private     ▾]
To account      [Household fund · Shared ▾]
Amount          [50,00 EUR                ]
Business date   [Today's date             ]

Checking −50,00 €  →  Household fund +50,00 €
[Cancel]                       [Confirm transfer]
```

Only offer writable, active accounts in the same household and exclude the selected source from destination choices. If fewer than two eligible accounts are available, explain what is needed before the user can transfer. Show both visibility labels and the direction before posting. Validate both accounts and the date on submission. The confirmation applies to both accounts and the transfer posts atomically.

In a scoped list, show the date, direction, and signed amount for the visible account. When both endpoints fall within the same list scope, show one transfer row with its source, destination, and amount. Account histories still show each account's own direction. In a mixed-visibility transfer, the actor can see both endpoints in the detail while both remain authorized. For a viewer with access to just one side, send only the authorized projection. Omit the hidden account's name, identifier, balance, and identifying notes from every response. Exclude those fields from search matching as well. Offer reversal only to a user who can post to both accounts.

For example, Ana transfers `50,00 €` from private Checking to the shared Household fund. Ana's Personal list shows `−50,00 €` and her Shared list shows `+50,00 €`. Marko, a household owner, sees the shared inflow and its business date under `Transfer from private account`. Marko's detail preserves that same view and the reversal action is unavailable. Neither view records transfer activity as income or spending.

### Reverse or Correct a Transaction

```text
Transaction                                     Groceries
Expense                                   −40,00 €
Checking · Private                      28.03.2026.
[Reverse transaction]                    [Correct transaction]

Reverse transaction
Original          Groceries · −40,00 € · 28.03.2026.
Reversal date     02.04.2026. (correction business date)
Reason            [Required                           ]
[Cancel]          [Confirm reversal]
```

Offer actions on authorized, eligible posted records. The reversal dialog explains that FinTrack will post the opposite entry on the correction business date and retain the original. Use the current business date for the MVP correction flow. Keep focus on `Cancel` initially. Confirm with a reason. After success, show the original as `Reversed` with a link to the reversal for an authorized viewer. If a concurrent action has already reversed it, refresh the original and show that result instead of inviting another submission.

For a financial correction with a replacement, open the relevant entry form with the original values. Show the original and the proposed replacement before `Confirm correction`. Both the reversal and the replacement use the correction business date and commit together. A failure leaves the original as it was and keeps the draft available to correct or retry.

### Empty, Loading, Error, and Success States

| Screen or flow | Empty or unavailable | Loading or saving | Error and recovery | Success |
|---|---|---|---|---|
| Overview | No accounts uses the action allowed for the viewer's role. Accounts with no activity show confirmed zero metrics for an empty period and an empty activity message. | Show metric and list placeholders in the active scope. | Explain which data failed and offer `Retry` without displaying placeholder zeros. | Show confirmed totals and activity for the selected date range. |
| Accounts | Personal offers `Create private account`. A shared owner sees `Create shared account`. Other members see `No shared accounts yet. A household owner can create one.` | Use list placeholders and descriptive saving or archiving labels. | Keep the account form values and show field errors. An unavailable account opens the generic access state. | Open the updated detail and show the confirmation or archival label. |
| Transactions | With no activity, explain how to add an entry if an eligible account is available. With filters and no matches, offer `Clear filters`. | Preserve filter controls and load a scoped results list. | Offer `Retry` with the same filters. An inaccessible detail opens the generic access state. | Show authorized results and keep the current filters visible. |
| Income or expense | If there is no writable account, explain what is needed to record an entry in this view. | Show `Saving transaction…` and block repeated submission. | Keep entered values and show amount, date, account, or category errors beside the related field. Show a clear explanation when the resulting cash balance would violate the ledger rules. | Show confirmation and refresh the originating view with a detail link. |
| Transfer | Explain when two eligible accounts in the same household are needed. | Show `Posting transfer…` and block repeated submission. | Keep the draft on validation rejection. For an account that became unavailable, ask the user to choose an eligible account without exposing details of inaccessible options. | Confirm one transfer and refresh the affected visible balances. |
| Reversal or correction | Show the current record status and offer actions only when eligible. | Show `Reversing transaction…` or `Correcting transaction…` and block repeated submission. | Retain the reason or replacement draft when validation fails. If the original is already reversed, refresh its detail. | Show the original's updated status and permitted links to the resulting entries. |

### Submission and Retry

Distinguish a server rejection from a missing response. After a confirmed validation rejection, return focus to the error, preserve the draft, and let the user submit a corrected attempt with a new key. When the result of a financial posting is unknown, show `We couldn't confirm whether this was saved.` Lock the submitted values for that attempt and offer `Retry same submission`. A retry uses the same idempotency key, household, and business inputs. A successful replay shows the existing result and refreshes the screen once. Leaving the page does not resend the request. If permissions have changed, use the generic access state.

### UX-03 Design Review

| Scenario | Expected result |
|---|---|
| Account creation and archival | A member creates a private Cash account. Its visibility is clear. After archiving, it stays in authorized history and is unavailable for new postings. The member sees explanatory text in an empty Shared view. |
| Income, expense, and filters | Post `1.000,00 €` income and `40,00 €` expense on the scoped checking account. Filter to expenses and see the grocery entry. Submitting `48,205` produces an amount error without posting and retains the other form values. |
| Repeated submission | Submit a `50,00 €` transfer and lose the response after commit. Retrying the same submission leaves one transfer and credits the destination once. A transfer attempt with the same account twice is rejected before posting. |
| Mixed visibility | Compare Ana's and Marko's view of the transfer above. Only Ana sees both endpoints and can request reversal. Marko's displayed row, detail, and search results contain only the authorized shared side. |
| Reversal across periods | With a `200,00 €` March opening balance and the income, expense, and transfer above, visible accounts in the ARC-04 fixture total `1.160,00 €` at the end of March. Reversing the March expense on 2 April leaves March income and spending at `1.000,00 €` and `40,00 €`. April spending is `−40,00 €`, and the current balance is `1.200,00 €` with no other activity. |

These are design walkthroughs. Browser, authorization, idempotency, and ledger checks belong to the implementation tasks.

## Review Before Implementation

- **UX-01:** Walk through first sign-in with and without a household, switching between personal and shared views in two households, and opening an inaccessible record. Check that totals, filters, and form destinations reflect the visible scope.
- **UX-02:** Check the applied color pairs, keyboard focus and order, amount input and formatting, and layouts at narrow widths and at browser zoom. Confirm that summary amounts and the context picker remain legible with long values and household names.
- **UX-03:** Use the design review scenarios above to verify the implemented screens, including validation, retries, access rules, and reversal dates.
