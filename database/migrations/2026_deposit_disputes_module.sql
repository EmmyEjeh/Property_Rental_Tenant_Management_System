-- Run once after backing up property_rental. Does not alter rent or tenancy records.
USE property_rental;
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
