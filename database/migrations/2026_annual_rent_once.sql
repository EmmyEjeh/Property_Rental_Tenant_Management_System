-- ONE-TIME MIGRATION: convert the existing monthly rent figures to annual rent.
-- BACK UP the property_rental database before running this script.
-- Run this script exactly once. Running it again would multiply annual rent by 12 again.
USE property_rental;

ALTER TABLE units
  CHANGE COLUMN monthlyRent annualRent DECIMAL(10,2) NOT NULL;

UPDATE units
SET annualRent = ROUND(annualRent * 12, 2);
