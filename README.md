dnbr-scupload
=============

Automated SoundCloud uploading of DJ sets from DNBRadio.com

Author / maintainer: [Will Morgan](http://willmorgan.co.uk)

### How it works (abstract)

0. OAuth authentication should take place
1. A worker fetches a RSS feed
2. The RSS feed is inspected for new entries. Any data is fed into a track object
3. These track objects are in turn set up in to a job queue
4. A separate worker performs the fetching and uploading of the track to Soundcloud

### Installation

Vagrant is required, but beyond that, all software required for development is taken care of by the
guest machine's provisioner. Dependencies and autoloading is managed by Composer.

#### Deployment

0. Check out the repository's `www` folder
1. Install composer, then run `composer install`
2. Set up a cron to run `php feed-worker.php` on whatever schedule is suitable (hourly works best)
3. Run `php upload-worker.php` through screen to start uploading tracks.

#### Development

0. Clone the repository
1. Run `vagrant up`
2. Run `php feed-worker.php` to fetch the RSS feed and populate the queue store
3. Run `php upload-worker.php` to start the queue processor and upload tracks.

##### Other notes

When running tasks, or when visiting `index.php`, a sanity check is performed by the app to ensure all
the configuration is in order. Any errors will be flagged.
