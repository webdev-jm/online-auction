# Online Auction — Developer Documentation

This document is for developers who maintain the Online Auction system or who are taking the source code over. It explains what the system does, how an auction runs from start to winner, how the code is organised, and what must be fixed before the next auction. It describes the `main` branch as of its last change in May 2026.

> **Keep this document private.** The folder ships with an `.htaccess` that only allows requests from the local machine.

## 1. System overview

### What the system does

The system runs internal auctions of company assets, such as used laptops, phones and equipment, to employees. An administrator lists the items and opens an auction for each one over a period of time. Employees place bids. When the period ends, the highest bid wins, the administrator prints the result and the winner is notified.

| Function | Description |
| --- | --- |
| Items | Items with pictures and specifications; Excel upload of many items. |
| Auctions | One auction per item: start and end date and time, minimum bid, visibility options. |
| Bidding | Employees accept the terms, place bids and see their own bids. |
| Results | The winner is determined when the auction ends; Excel export, PDF print, winner notification. |
| QR codes | A page that generates a QR code for a link, for posters. |
| Administration | Companies, users, roles, activity log. |

Production host: `online-auction.bevi.ph`.

### Vocabulary

| Term | Meaning in this code base |
| --- | --- |
| Item | The thing sold. `items`, with `item_pictures` and `item_specifications`. |
| Auction | The sale of one item over a period. `auctions`, identified by `auction_code`. |
| Bidding | One bid by one user. `biddings`. |
| Auction winner | The winning bid of an auction. `auction_winners`. |
| Bid limit | The maximum number of bids one user may place in an auction. Empty means no limit. |

### Technology stack

| Layer | What is used |
| --- | --- |
| Language and framework | PHP 8.1+, Laravel 10 |
| Interactive UI | Livewire 3 (class-based), Blade |
| Admin theme | `jeroennoten/laravel-adminlte` 3 |
| Authentication | `laravel/ui` session login **with self-registration**, Google sign-in; the Hub client package is installed but its login button was removed |
| Authorisation | `spatie/laravel-permission` 6 |
| Audit trail | `spatie/laravel-activitylog` 4 |
| Excel and PDF | `maatwebsite/excel`, `phpoffice/phpspreadsheet`, `barryvdh/laravel-dompdf` |
| Images and codes | `intervention/image` 2, `milon/barcode` |
| Asset build | Vite 5 |
| Tests | PHPUnit 10 (only the two example tests) |
| Database | MySQL |

### Repository facts

| Item | Value |
| --- | --- |
| Remote | `https://github.com/webdev-jm/online-auction.git` |
| Default branch | `main` |
| History | First commit February 24, 2025; last change May 4, 2026; about 90 commits |
| Production path | `/home/kojiesanadmin/public_html/online-auction.bevi.ph` |
| Related files | `2025/PROJECT-DOCS/AUCTION DOCS/ITEMS UPLOAD.xlsx` on the original developer's machine is a sample item upload |

An older working copy and an `auction-starter` skeleton exist under `2025/PROJECT-DOCS`. The copy under `2026/PROJECTS` is the current one. The root `README.md` is the stock Laravel README.

## 2. Getting started

`composer.json` declares a path repository at `../hub-auth-client`. Clone that package into a sibling folder first, or Composer fails.

```bash
git clone https://github.com/webdev-jm/online-auction.git
cd online-auction
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
php artisan serve
```

The seeders create permissions, the roles `superadmin` and `user`, a first user and companies. Nothing runs in the background: no queue worker and no scheduler are needed.

### Environment variables

| Variable | Purpose |
| --- | --- |
| `DB_*` | Application database. |
| `MAIL_*` | Bid confirmations and winner notifications are sent by e-mail. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Google sign-in. |
| `HUB_*` | Read by `config/hub-auth.php`. The Hub login was removed from the login page in May 2026, so these are currently unused. |
| `config/app.php` `timezone` | Set to `Asia/Manila` in the file. **Auction start and end are compared with the application clock**, so do not change it without reason. |

## 3. Architecture

### Directory guide

```
app/
  Exports/AuctionExport.php        bids of an auction to Excel
  Imports/ItemsImport.php          item upload
  Helpers/GetModelRelationship.php
  Http/Controllers/
    AuctionController      auctions, export, print, winner notification
    BiddingController      bidding page, bid list, "my bids"
    ItemController         items and upload page
    CompanyController, UserController, RoleController, SystemLogController, HomeController
    Auth/                  login, registration, Google sign-in
  Http/Requests/           AuctionAddRequest, AuctionEditRequest, and the usual Add and Edit requests
  Livewire/
    Auctions/Tab.php       status, countdown and winner of one auction
    Biddings/Bid.php       the bid form
    Items/Form.php, Items/Upload.php
    Users/Bids.php         a user's bidding history
    TermsAgreement.php     accept or decline the auction terms
    QrGenerator.php
    Roles/Users.php, DeleteModel.php
  Models/                  Auction, AuctionWinner, Bidding, Item, ItemPicture, ItemSpecification, Company, User
  Notifications/           BidNotification, AuctionWinnerNotification
public/uploads/            item pictures
resources/views/pages/{module}, resources/views/livewire
```

### Data model

```
companies 1──* users 1──* biddings *──1 auctions *──1 items 1──* item_pictures
                                          │                   └──* item_specifications
                                          └──1 auction_winners ──1 biddings
```

`auctions` columns: `item_id`, `company_id`, `auction_code`, `status`, `start`, `start_time`, `end`, `end_time`, `min_bid`, `bid_limit`, `show_bidders`, `show_leading_bidder`, `show_last_place_bidder`, `restrict_to_company_only`.

### Routes and permissions

| Group | Routes | Permissions |
| --- | --- | --- |
| Bidding | `/bidding/{id}`, `/bidding/{id}/list`, `/my-bid` | `bidding access`, `bidding list`, `bidding leader` |
| Auctions | `/auctions`, `/auction/create`, `/auction/{id}`, `/auction/{id}/edit`, `/auction-export/{id}`, `/auction-print/{id}`, `/auction-send-winner/{id}` | `auction access`, `create`, `edit`, `delete`, `export`, `print` |
| Items | `/items`, `/item/create`, `/item/upload`, `/item/{id}`, `/item/{id}/edit` | `item access`, `create`, `edit`, `delete`, `upload` |
| Administration | `/companies`, `/users`, `/roles`, `/system-logs` | `company`, `user`, `role`, `system logs` |
| Open | `/` (welcome page with auctions), `/qr-generator` | none |

The `user` role is what bidders receive, including people who sign in with Google for the first time. Ids in URLs are encrypted.

## 4. How an auction runs

### Setting up

1. Create items by form (`Items\Form`) or by Excel (`Items\Upload`, `ItemsImport`). Pictures are stored under `public/uploads`.
2. Create an auction (`AuctionController::store`). Only items without a current auction are offered. `generateAuctionCode()` assigns the code.
3. Options on the auction:

| Option | Effect |
| --- | --- |
| `min_bid` | The lowest amount accepted |
| `bid_limit` | How many bids one user may place; empty for no limit |
| `show_bidders` | Bidders' names are visible in the bid list |
| `show_leading_bidder` | Everyone sees the current highest bid; otherwise only holders of `bidding leader` |
| `show_last_place_bidder` | Shows the lowest of the users' best bids |
| `restrict_to_company_only` | Only users of the auction's company may take part |

### Status

There is no stored schedule. `Auctions\Tab::checkStatus()` compares the server time with the start and end and writes the result to `auctions.status` **each time the component is rendered**:

| Condition | Status |
| --- | --- |
| Before start | `UPCOMING` |
| Between start and end | `OPEN` |
| After end | `ENDED` |

The same component shows the countdown (`getTimeRemaining()`).

### Bidding

1. The bidder opens `/bidding/{id}`. `TermsAgreement` asks them to accept the terms first.
2. `Biddings\Bid::PlaceBid()` validates that the amount is at least `min_bid`, saves a `Bidding`, writes an activity log entry and sends `BidNotification` to the bidder.
3. The component reloads the bidder's own bids (`checkDuplicateBid()`), and the leading and last-place bids according to the auction's options.

### Winner

When the status is `ENDED`, `Tab::getAuctionWinner()` takes the bid with the **highest amount, earliest first in case of a tie**, and saves or updates the `auction_winners` row. The administrator then:

- exports the bids (`AuctionExport`),
- prints the result (`pages/auctions/pdf.blade.php`),
- sends `AuctionWinnerNotification` with `/auction-send-winner/{id}`.

## 5. Weak points to fix before the next auction

These are properties of the current code that matter when money and fairness are involved. They are listed here so that the next maintainer deals with them first.

| Point | Detail | Suggested fix |
| --- | --- | --- |
| The bid form does not re-check that the auction is open | `PlaceBid()` checks only the minimum amount. Whether bidding is possible is decided by what the page shows. | In `PlaceBid()`, reload the auction and reject the bid unless the current time is between start and end. |
| The bid limit is enforced only in the view | The button is hidden when the user has reached `bid_limit`; the method does not count. | Count the user's bids inside `PlaceBid()`, in a transaction. |
| The company restriction is enforced only in the views | `restrict_to_company_only` is tested in `livewire/biddings/bid.blade.php` and `livewire/terms-agreement.blade.php`, not in PHP. | Add the check to `BiddingController` and `PlaceBid()`. |
| A new bid need not beat the current highest | Any amount at or above the minimum is accepted. This may be intended (sealed-bid style); confirm with the business owner. | If not intended, require the amount to exceed the current highest bid. |
| Status and winner depend on someone viewing the auction | An auction nobody opens after its end keeps the status `OPEN` in the database and has no winner row. | A scheduled command that closes ended auctions and records winners. |
| An ended auction with no bids | `getAuctionWinner()` updates an existing winner row from a bid that may not exist. | Guard against a missing highest bid. |
| Registration is open | `Auth::routes()` is called without options, so `/register` and password reset are available to anyone who can reach the site. | Disable registration, or restrict it to company e-mail domains, if only employees should bid. |
| Dates and times | Start and end are stored as separate date and time columns and compared through `strtotime()`. | Store them as datetimes and compare with Carbon. |
| No tests | Bidding rules are not covered by any test. | Write feature tests for each rule above before changing the code. |

## 6. Maintenance procedures

### Adding an auction option

Migration on `auctions`, `Auction::$fillable`, both form requests, `AuctionController::store()` and `update()`, the create and edit views, and the place where it takes effect (`Biddings\Bid` or `Auctions\Tab`). Enforce it in PHP, not only in the Blade view.

### Item upload format

`ItemsImport` defines the expected columns. Keep the sample workbook in step with it and consider placing a template in `public/assets` so administrators can download it.

### Assets

`npm run dev` or `npm run build`; commit `public/build` if the server does not run Node.

## 7. Deployment

`.cpanel.yml` copies the repository to `/home/kojiesanadmin/public_html/online-auction.bevi.ph`.

1. `npm run build` and commit the build.
2. Merge to `main`, deploy from cPanel.
3. On the server: `composer install --no-dev --optimize-autoloader` (the `hub-auth-client` path must exist beside the project), `php artisan migrate --force`, `php artisan optimize:clear`.

Back up the database and `public/uploads`. **Do not deploy while an auction is open.**

## 8. Troubleshooting

| Symptom | Likely cause and fix |
| --- | --- |
| An auction opens or closes at the wrong time | Server or application time zone. |
| An ended auction still shows as open in a list | Its status is only refreshed when its tab component renders. |
| No winner recorded | Nobody opened the auction after it ended, or there were no bids. |
| Bidders see each other's names | `show_bidders` is on for that auction. |
| Item pictures are missing | `public/uploads` was not copied, or the storage link is missing. |
| No bid confirmation e-mail | Mail settings; the failure is swallowed by an empty `catch` in `PlaceBid()`, so check the mail configuration directly. |
| "The payload is invalid" | A plain id in a URL. |
| Composer cannot find `hub-auth-client` | The sibling folder is missing. |

## 9. Handover checklist

- [ ] GitHub access to `webdev-jm/online-auction` and `hub-auth-client`
- [ ] cPanel access and the deployment trigger
- [ ] Production `.env`
- [ ] Database credentials and a dump of past auctions
- [ ] A copy of `public/uploads`
- [ ] Mail account; Google OAuth client
- [ ] The auction mechanics and terms as approved by the business owner (they decide several questions in section 5)
- [ ] The date of the next planned auction
- [ ] A superadmin account for the new maintainer
