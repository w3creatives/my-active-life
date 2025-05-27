# Deployment Notes

### Table of Contents
- [Database changes](#database-changes)

## Database changes

### Table `modality`
- Added `value` column to `modality` table to store the bit value for each modality.
- Updated `modality` table to include `daily_steps` with a value of `1`.
- Updated `modality` table to include `run` with a value of `2`.
- Updated `modality` table to include `walk` with a value of `4`.
- Updated `modality` table to include `bike` with a value of `8`.
- Updated `modality` table to include `swim` with a value of `16`.
- Updated `modality` table to include `other` with a value of `32`.
- Updated `index` for each row
