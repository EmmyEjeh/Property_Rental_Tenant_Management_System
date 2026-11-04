-- Schema aligned with Chapter Three, Section 3.8. Review and test before production use.


CREATE TABLE IF NOT EXISTS users (
  userId INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  passwordHash VARCHAR(255) NOT NULL,
  role ENUM('manager','tenant') NOT NULL,
  fullName VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  email VARCHAR(100) NULL UNIQUE,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS properties (
  propertyId INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  managerId INT NOT NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_properties_manager FOREIGN KEY (managerId) REFERENCES users(userId)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS units (
  unitId INT AUTO_INCREMENT PRIMARY KEY,
  propertyId INT NOT NULL,
  unitNumber VARCHAR(20) NOT NULL,
  status ENUM('occupied','vacant') NOT NULL DEFAULT 'vacant',
  annualRent DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_unit_property (propertyId, unitNumber),
  CONSTRAINT chk_unit_rent CHECK (annualRent >= 0),
  CONSTRAINT fk_units_property FOREIGN KEY (propertyId) REFERENCES properties(propertyId)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenants (
  tenantId INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NULL UNIQUE,
  fullName VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100) NULL,
  unitId INT NULL,
  leaseStart DATE NOT NULL,
  leaseEnd DATE NOT NULL,
  cautionFee DECIMAL(10,2) NOT NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_tenant_lease CHECK (leaseEnd >= leaseStart),
  CONSTRAINT chk_tenant_caution CHECK (cautionFee >= 0),
  CONSTRAINT fk_tenants_user FOREIGN KEY (userId) REFERENCES users(userId)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_tenants_unit FOREIGN KEY (unitId) REFERENCES units(unitId)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenancies (
  tenancyId INT AUTO_INCREMENT PRIMARY KEY,
  tenantId INT NOT NULL,
  unitId INT NOT NULL,
  startDate DATE NOT NULL,
  endDate DATE NOT NULL,
  cautionFee DECIMAL(10,2) NOT NULL,
  status ENUM('active','ended') NOT NULL DEFAULT 'active',
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_tenancy_dates CHECK (endDate >= startDate),
  CONSTRAINT chk_tenancy_caution CHECK (cautionFee >= 0),
  CONSTRAINT fk_tenancies_tenant FOREIGN KEY (tenantId) REFERENCES tenants(tenantId)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_tenancies_unit FOREIGN KEY (unitId) REFERENCES units(unitId)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  paymentId INT AUTO_INCREMENT PRIMARY KEY,
  tenancyId INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  paymentDate DATE NOT NULL,
  periodCovered VARCHAR(50) NULL,
  method ENUM('cash','transfer','other') NOT NULL,
  receiptNumber VARCHAR(50) NULL UNIQUE,
  createdBy INT NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_payment_amount CHECK (amount > 0),
  CONSTRAINT fk_payments_tenancy FOREIGN KEY (tenancyId) REFERENCES tenancies(tenancyId)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_payments_user FOREIGN KEY (createdBy) REFERENCES users(userId)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance_requests (
  requestId INT AUTO_INCREMENT PRIMARY KEY,
  tenancyId INT NOT NULL,
  description TEXT NOT NULL,
  dateSubmitted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','in_progress','resolved') NOT NULL DEFAULT 'pending',
  photoPath VARCHAR(255) NULL,
  updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_maintenance_tenancy FOREIGN KEY (tenancyId) REFERENCES tenancies(tenancyId)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dispute_cases (
  disputeId INT AUTO_INCREMENT PRIMARY KEY,
  tenancyId INT NOT NULL,
  moveInPhotos TEXT NULL,
  moveOutPhotos TEXT NULL,
  aiClassification VARCHAR(100) NULL,
  recommendedDeduction DECIMAL(10,2) NULL,
  finalDeduction DECIMAL(10,2) NULL,
  status ENUM('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
  resolvedDate DATE NULL,
  aiJustification TEXT NULL,
  refundAmount DECIMAL(10,2) NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_dispute_recommended CHECK (recommendedDeduction IS NULL OR recommendedDeduction >= 0),
  CONSTRAINT chk_dispute_final CHECK (finalDeduction IS NULL OR finalDeduction >= 0),
  CONSTRAINT fk_disputes_tenancy FOREIGN KEY (tenancyId) REFERENCES tenancies(tenancyId)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dispute_evidence (
  evidenceId INT AUTO_INCREMENT PRIMARY KEY,
  disputeId INT NOT NULL,
  phase ENUM('move_in','move_out') NOT NULL,
  fileName VARCHAR(255) NOT NULL,
  storedName VARCHAR(255) NOT NULL,
  mimeType VARCHAR(80) NOT NULL,
  fileSize INT NOT NULL,
  caption VARCHAR(255) NULL,
  uploadedBy INT NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dispute_evidence (disputeId, phase, evidenceId),
  CONSTRAINT fk_evidence_dispute FOREIGN KEY (disputeId) REFERENCES dispute_cases(disputeId) ON DELETE CASCADE,
  CONSTRAINT fk_evidence_user FOREIGN KEY (uploadedBy) REFERENCES users(userId) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dispute_assessments (
  assessmentId INT AUTO_INCREMENT PRIMARY KEY,
  disputeId INT NOT NULL,
  engine VARCHAR(80) NOT NULL,
  resultJson JSON NOT NULL,
  createdBy INT NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_assessment_dispute (disputeId, createdAt),
  CONSTRAINT fk_assessment_dispute FOREIGN KEY (disputeId) REFERENCES dispute_cases(disputeId) ON DELETE CASCADE,
  CONSTRAINT fk_assessment_user FOREIGN KEY (createdBy) REFERENCES users(userId) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  auditId BIGINT AUTO_INCREMENT PRIMARY KEY,
  userId INT NULL,
  action VARCHAR(100) NOT NULL,
  entityType VARCHAR(80) NOT NULL,
  entityId VARCHAR(64) NULL,
  details JSON NULL,
  ipAddress VARCHAR(45) NULL,
  createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entityType, entityId),
  INDEX idx_audit_created (createdAt),
  CONSTRAINT fk_audit_user FOREIGN KEY (userId) REFERENCES users(userId)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_tenancies_tenant ON tenancies(tenantId);
CREATE INDEX idx_tenancies_unit_status ON tenancies(unitId, status);
CREATE INDEX idx_payments_tenancy_date ON payments(tenancyId, paymentDate);
CREATE INDEX idx_maintenance_status ON maintenance_requests(status, dateSubmitted);
CREATE INDEX idx_disputes_status ON dispute_cases(status, createdAt);
