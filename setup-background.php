#!/usr/bin/env php
<?php
/**
 * Background Setup Script
 * Usage: php setup-background.php <source-image-path> [--blur-radius=20]
 * 
 * This script generates a blurred background from the provided image
 * and sets it up for use throughout the POS system.
 */

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Parse arguments
$args = $_SERVER['argv'];
array_shift($args); // Remove script name

if (empty($args)) {
    echo "Background Setup Script\n";
    echo "=======================\n\n";
    echo "Usage: php setup-background.php <source-image> [--blur-radius=20]\n\n";
    echo "Arguments:\n";
    echo "  <source-image>      Path to the source image file (JPG, PNG, etc.)\n";
    echo "  --blur-radius       Blur intensity (default: 20, range: 5-50)\n\n";
    echo "Example:\n";
    echo "  php setup-background.php /path/to/image.jpg --blur-radius=25\n\n";
    die();
}

$sourceImage = $args[0];
$blurRadius = 20;

// Parse options
foreach ($args as $i => $arg) {
    if (strpos($arg, '--blur-radius=') === 0) {
        $blurRadius = (int)substr($arg, strlen('--blur-radius='));
        $blurRadius = max(5, min(50, $blurRadius)); // Clamp between 5 and 50
    }
}

// Validate source image
if (!file_exists($sourceImage)) {
    echo "Error: Source image not found: {$sourceImage}\n";
    die(1);
}

$info = getimagesize($sourceImage);
if ($info === false) {
    echo "Error: Unable to read image properties\n";
    die(1);
}

echo "Background Setup Script\n";
echo "=======================\n\n";
echo "Source Image: " . basename($sourceImage) . "\n";
echo "Dimensions: {$info[0]} x {$info[1]} pixels\n";
echo "File Size: " . number_format(filesize($sourceImage) / 1024, 2) . " KB\n";
echo "Blur Radius: {$blurRadius}px\n\n";

echo "Processing...\n";
echo "Step 1: Copying original image...\n";

$publicImgDir = dirname(__DIR__) . '/public/img';
if (!is_dir($publicImgDir)) {
    mkdir($publicImgDir, 0755, true);
}

$originalOutput = $publicImgDir . '/background-original.jpg';
$blurredOutput = $publicImgDir . '/background-blurred.jpg';

// Copy original
if (!copy($sourceImage, $originalOutput)) {
    echo "Error: Failed to copy original image\n";
    die(1);
}
echo "✓ Original saved to: background-original.jpg\n";

// Generate blurred version
echo "Step 2: Generating blurred background...\n";

$result = createBlurredImage($sourceImage, $blurredOutput, $blurRadius);

if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
    echo "✓ Method: " . $result['method'] . "\n";
    echo "✓ Output: background-blurred.jpg\n\n";
    
    // Verify files
    echo "Verification:\n";
    echo "✓ Original: " . number_format(filesize($originalOutput) / 1024, 2) . " KB\n";
    echo "✓ Blurred: " . number_format(filesize($blurredOutput) / 1024, 2) . " KB\n\n";
    
    echo "Setup Complete!\n";
    echo "===============\n";
    echo "Your blurred background is ready to use.\n";
    echo "Background CSS: resources/css/background.css\n";
    echo "Background images: public/img/background-*.jpg\n\n";
    
    die(0);
} else {
    echo "Error: " . $result['message'] . "\n";
    die(1);
}

/**
 * Create blurred image using available image libraries
 */
function createBlurredImage($source, $output, $blurRadius)
{
    // Try ImageMagick first
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick($source);
            $imagick->blurImage($blurRadius, $blurRadius);
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(75);
            $imagick->writeImage($output);
            $imagick->clear();
            
            return [
                'success' => true,
                'message' => 'Blurred background generated successfully',
                'method' => 'ImageMagick'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Fallback to GD library
    if (extension_loaded('gd')) {
        try {
            $image = imagecreatefromjpeg($source);
            if (!$image) {
                throw new Exception("Failed to load JPEG image");
            }
            
            // Apply blur filters
            for ($i = 0; $i < ceil($blurRadius / 5); $i++) {
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            }
            
            imagejpeg($image, $output, 75);
            imagedestroy($image);
            
            return [
                'success' => true,
                'message' => 'Blurred background generated successfully',
                'method' => 'GD Library'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'No image processing library available (ImageMagick or GD required)'
    ];
}
