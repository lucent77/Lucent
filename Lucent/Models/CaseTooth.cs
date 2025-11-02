using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace Lucent.Models
{
    [Table("CaseTeeth")]
    public class CaseTooth
    {
        [Key]
        public int CaseToothId { get; set; }

        [Required]
        public int CaseId { get; set; }

        [Required]
        [StringLength(5)]
        public string ToothNumber { get; set; }

        [StringLength(50)]
        public string ToothType { get; set; }

        [StringLength(50)]
        public string ShadeVendor { get; set; }

        [StringLength(50)]
        public string Color { get; set; }

        [StringLength(50)]
        public string MillingColor { get; set; }

        [StringLength(50)]
        public string TissueShade { get; set; }

        public int Quantity { get; set; }

        [StringLength(200)]
        public string Notes { get; set; }

        public DateTime CreatedAt { get; set; }

        // Navigation property
        [ForeignKey("CaseId")]
        public virtual Case Case { get; set; }
    }
}
