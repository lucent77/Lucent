using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace Lucent.Models
{
    [Table("Cases")]
    public class Case
    {
        [Key]
        public int CaseId { get; set; }

        [Required]
        [StringLength(50)]
        public string CaseNumber { get; set; }

        [Required]
        [StringLength(100)]
        public string PatientName { get; set; }

        [StringLength(100)]
        public string LabName { get; set; }

        [StringLength(100)]
        public string AccountName { get; set; }

        [Required]
        public WorkType WorkType { get; set; }

        [Required]
        public CaseStatus Status { get; set; }

        [Required]
        public Location Location { get; set; }

        [StringLength(50)]
        public string CaseType { get; set; }

        public DateTime OrderDate { get; set; }

        public DateTime? DueDate { get; set; }

        public DateTime? ShipDate { get; set; }

        public DateTime? AppointmentDate { get; set; }

        [StringLength(50)]
        public string TrackingNumber { get; set; }

        [StringLength(500)]
        public string Instructions { get; set; }

        [StringLength(500)]
        public string Preferences { get; set; }

        public DateTime CreatedAt { get; set; }

        public DateTime UpdatedAt { get; set; }

        [StringLength(100)]
        public string CreatedBy { get; set; }

        // Navigation properties
        public virtual ICollection<CaseDetail> CaseDetails { get; set; }
        public virtual ICollection<CaseNote> CaseNotes { get; set; }
        public virtual ICollection<CaseTooth> CaseTeeth { get; set; }
    }
}
