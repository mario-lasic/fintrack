# FinTrack Financial Domain and Privacy

This document records the agreed design for the planned MVP financial model. The ERD and PostgreSQL integrity constraints are covered separately by ARC-03.

## ARC-01 — Domain Boundaries and Terminology

| Concept | Responsibility |
| --- | --- |
| `Account` | A financial account the user manages, such as checking, savings, or cash. It belongs to a household and carries its display name, type, and visibility. Its balance is derived from postings to its mapped ledger account. |
| `LedgerAccount` | A bookkeeping account that receives postings. Each `Account` maps to its own `asset` ledger account. The ledger also uses internal `income`, `expense`, and `opening equity` accounts. |
| `Transaction` | The user-facing record of income, an expense, or a transfer. It carries business context such as type, business date, actor, and category where applicable. It links to its journal entry, which supplies the financial amounts. |
| `JournalEntry` | A complete accounting record for a financial event. All its postings are recorded atomically, and total debits equal total credits. |
| `Posting` | One line in a journal entry. It names a ledger account, a `debit` or `credit` side, and a positive amount in integer EUR cents. A debit increases an asset balance and a credit decreases it. |

The following examples use an existing checking balance and an empty savings account. Each event creates one `JournalEntry` and two `Posting` records.

| Event | User-facing activity | `Transaction` created | Debit | Credit |
| --- | --- | --- | --- | --- |
| €200 opening balance | Opening balance on checking | No | `Checking` 20,000 cents | `Opening Equity` 20,000 cents |
| €1,000 salary payment | One income item on checking | Yes | `Checking` 100,000 cents | `Income` 100,000 cents |
| €40 grocery purchase | One expense on checking | Yes | `Expense` 4,000 cents | `Checking` 4,000 cents |
| €50 transfer from checking to savings | One transfer, shown as €50 out in checking history and €50 in in savings history | Yes | `Savings` 5,000 cents | `Checking` 5,000 cents |

The final balances are €1,110 in checking and €50 in savings. The income and expense totals are €1,000 and €40. Opening balances and transfers affect account balances and are excluded from income and expense totals.

A user can start with a single checking account, such as a Croatian bank's EUR current account. An ordinary debit card purchase is recorded as an expense on that account. Card registration and separate tracking of cards, credit limits, and credit card debt are deferred beyond the MVP.

## ARC-02 — Household Roles and Privacy

The `owner` and `member` roles apply within a household. A private account is accessible only to its account owner, who must be an active member of the same household. A household owner has no special access to another person's private account.

| Action | Household `owner` | Household `member` | No active membership |
| --- | --- | --- | --- |
| Manage household settings, invitations, and membership | Yes | No | No |
| View and post to own private account | Yes | Yes | No |
| View or post to someone else's private account | No | No | No |
| View shared accounts, balances, and history | Yes | Yes | No |
| Record income or expenses on an active shared account | Yes | Yes | No |
| Open, rename, or archive an account | Own private accounts and shared accounts | Own private accounts | No |

Account visibility is set at creation. Access ends immediately when membership ends. Account detail, search, reports, and exports use the same authorized scope. Balance totals include only accounts the viewer can access.

A transfer requires two distinct accounts in the same household and permission to post to both. Reversing a transfer requires permission to post to both accounts as well. When a viewer can see only one side, the record shows the visible account's direction, amount, and date. It omits the hidden account's name, identifier, and balance, along with any note that could identify it.

- **Allowed.** Ana, a `member`, transfers €50 from her private checking account to a shared account. She can see both sides. Marko, an `owner` of the household, sees the incoming €50 transfer and its date on the shared account.
- **Denied.** Marko cannot open Ana's private account through a direct link or reverse the transfer. The shared account activity remains visible to him.
- **Denied.** Ana cannot transfer directly between accounts in two different households, even if she is an active member of both.
