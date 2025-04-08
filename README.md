# Fits in Media for Drupal

## Introduction

This Drupal 8/9 module consumes File Information Tool Set (Fits) to retrieve and extract technical metadata for media.

# Using Drush Commands

To index the media for extracting FITS, you can use the command media-fits:queue or mfq. You can use the command in the two following ways:
- Using CSV file to index
    - You can list the media IDS in a csv file. Note: the first row will be skipped and is treated as a header.
    - Then using the absolute path of the csv, you can use the csv flag with the command. For example- media-fits:queue --csv path/to/media_ids.csv
- Listing the media in the shell
    - You can list the media IDS directly in the shell as well. Note: the media ids must be separated by commas without any space between them.
    - The you can use the mids flag with the command. For example- media-fits:queue --mids 1,2,3,4