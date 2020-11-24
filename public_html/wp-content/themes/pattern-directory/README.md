# WordPress.org Pattern Directory Theme


### Getting Started

* Run `npm install` in this directory
* If you're running this locally, then install the `wporg` parent theme:

    ```sh
    svn co https://meta.svn.wordpress.org/sites/trunk/wordpress.org/public_html/wp-content/themes/pub/wporg path/to/wp-content/themes/wporg`
    ```

### Development

Run `npm run start` while editing SASS files, and they'll be compiled to vanilla CSS in `css/style.css`.

Run `npm run build` before committing, so the production file is small.
