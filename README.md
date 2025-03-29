This is now being maintained at:
https://github.com/gbti-network/mu-plugin-clean-image-meta

# Clean Image Metadata

A WordPress must-use plugin that automatically removes all metadata (EXIF, IPTC, XMP, etc.) from uploaded images.

## Description

Clean Image Metadata strips sensitive and unnecessary metadata from images when they're uploaded to your WordPress site. This includes:

- EXIF data (camera information, GPS coordinates, date/time)
- IPTC data (copyright, credits, keywords)
- XMP data (AI generation information, editing history)
- Other embedded metadata

The plugin works silently in the background, requiring no configuration or user interaction.

## Benefits

### Privacy Enhancement
- Prevents leakage of sensitive location data
- Removes device and software identifiers
- Eliminates timestamps and other sensitive information

### Avoiding Bias
- Removes AI attribution information that could lead to content discrimination
- Eliminates potential biases based on equipment, location, or creator identity
- Creates a level playing field for all images regardless of origin

### Technical Advantages
- Potentially smaller file sizes
- Reduced risk of metadata-based vulnerabilities
- Simplified image asset management

## Installation

As a must-use plugin, installation is straightforward:

1. Upload the `clean-image-meta.php` file to your `/wp-content/mu-plugins/` folder
2. The plugin activates automatically (no action required in WordPress admin)
3. All new image uploads will now have metadata automatically removed

If your site doesn't have a `mu-plugins` directory, create it in your `wp-content` folder first.

## How It Works

The plugin:
1. Hooks into WordPress's upload process to intercept image files
2. Uses PHP's GD library to process images and strip metadata
3. Falls back to ImageMagick if available when needed
4. Preserves image quality and visual appearance
5. Handles all common image formats (JPEG, PNG, WebP, GIF)
6. Processes both original uploads and all generated image sizes/thumbnails


## Troubleshooting

If you encounter issues:

1. Ensure your server has GD library installed and enabled
2. Check that your WordPress installation has proper file permissions
3. For large images, verify your PHP memory limits are sufficient
4. Test with different image formats if experiencing format-specific issues

## Feedback and Support

For questions, feature requests, or bug reports, please contact the plugin author.
