using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace Lucent.Models
{
    [Table("CaseNotes")]
    public class CaseNote
    {
        [Key]
        public int CaseNoteId { get; set; }

        [Required]
        public int CaseId { get; set; }

        [Required]
        [StringLength(50)]
        public string NoteType { get; set; }

        [StringLength(200)]
        public string Subject { get; set; }

        [Required]
        [StringLength(2000)]
        public string Note { get; set; }

        [StringLength(100)]
        public string CreatedBy { get; set; }

        public DateTime CreatedAt { get; set; }

        // Navigation property
        [ForeignKey("CaseId")]
        public virtual Case Case { get; set; }
    }
}
