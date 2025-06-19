# Registration Source

## Description

Registration Source is a WordPress plugin that captures and displays the registration source for users who register on your website. It provides functionality to track whether users registered via the native registration form, XML-RPC, or REST API.

**Author:** [hsurekar](https://github.com/hsurekar/)

## Features

- Captures and stores the registration source for each user.
- Displays the registration source in the user list on the WordPress admin backend.

## Installation

1. Download the ZIP file from the [Releases](https://github.com/hsurekar/registration-source/releases) page.
2. In your WordPress admin dashboard, go to "Plugins" -> "Add New".
3. Click the "Upload Plugin" button and upload the downloaded ZIP file.
4. Activate the plugin.

## Usage

Once the plugin is activated, it will automatically capture the registration source based on the context of the registration request. The registration source will be displayed in the "Registration Source" column in the user list on the WordPress admin backend.

## Compatibility

This plugin is tested and compatible with WordPress 6.4.

## Contributing

Contributions are welcome! If you find any issues or want to improve the plugin, please submit a [Pull Request](https://github.com/hsurekar/registration-source/pulls).

## Support

For any questions or issues, please create a [new issue](https://github.com/hsurekar/registration-source/issues).

## License

This plugin is released under the [MIT License](LICENSE).

## Changelog

### 1.1.0
- Fixed function naming convention to use proper prefixes
- Updated WordPress compatibility to version 6.4
- Added proper changelog documentation
- Fixed version numbers to be consistent across files
- Improved code organization and security
- Added REST API integration
- Added admin interface for viewing registration statistics
- Added support for tracking registration sources
