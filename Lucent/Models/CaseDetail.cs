using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace Lucent.Models
{
    [Table("CaseDetails")]
    public class CaseDetail
    {
        [Key]
        public int CaseDetailId { get; set; }

        [Required]
        public int CaseId { get; set; }

        // SOLIDEX specific fields
        [StringLength(50)]
        public string Combo { get; set; }

        [StringLength(50)]
        public string LD { get; set; }

        [StringLength(100)]
        public string Pan { get; set; }

        [StringLength(100)]
        public string Design { get; set; }

        [StringLength(100)]
        public string Nesting { get; set; }

        // 3D PRINT specific fields
        [StringLength(100)]
        public string PrintType { get; set; }

        [StringLength(100)]
        public string ImplantType { get; set; }

        [StringLength(50)]
        public string Transparency { get; set; }

        [StringLength(500)]
        public string Note { get; set; }

        // ZEST specific fields
        [StringLength(50)]
        public string CADEMAX { get; set; }

        [StringLength(50)]
        public string MCIOFL { get; set; }

        [StringLength(50)]
        public string FC { get; set; }

        [StringLength(50)]
        public string AH { get; set; }

        [StringLength(50)]
        public string CONTACTS { get; set; }

        [StringLength(50)]
        public string OCC { get; set; }

        [StringLength(100)]
        public string DiskType { get; set; }

        [StringLength(50)]
        public string EmaxColor { get; set; }

        [StringLength(50)]
        public string ZestType { get; set; }

        public bool? Remake { get; set; }

        [StringLength(500)]
        public string SpecialInstructions { get; set; }

        public DateTime CreatedAt { get; set; }

        public DateTime UpdatedAt { get; set; }

        // Navigation property
        [ForeignKey("CaseId")]
        public virtual Case Case { get; set; }
    }
}
