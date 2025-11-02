using System;
using System.Collections.Generic;

namespace Lucent.Models.DTOs
{
    public class CaseCreateDto
    {
        public string CaseNumber { get; set; }
        public string PatientName { get; set; }
        public string LabName { get; set; }
        public string AccountName { get; set; }
        public WorkType WorkType { get; set; }
        public Location Location { get; set; }
        public string CaseType { get; set; }
        public DateTime? DueDate { get; set; }
        public DateTime? AppointmentDate { get; set; }
        public string Instructions { get; set; }
        public string Preferences { get; set; }

        // Case Details
        public CaseDetailDto Details { get; set; }

        // Teeth
        public List<CaseToothDto> Teeth { get; set; }
    }

    public class CaseDetailDto
    {
        // SOLIDEX
        public string Combo { get; set; }
        public string LD { get; set; }
        public string Pan { get; set; }
        public string Design { get; set; }
        public string Nesting { get; set; }

        // 3D PRINT
        public string PrintType { get; set; }
        public string ImplantType { get; set; }
        public string Transparency { get; set; }
        public string Note { get; set; }

        // ZEST
        public string CADEMAX { get; set; }
        public string MCIOFL { get; set; }
        public string FC { get; set; }
        public string AH { get; set; }
        public string CONTACTS { get; set; }
        public string OCC { get; set; }
        public string DiskType { get; set; }
        public string EmaxColor { get; set; }
        public string ZestType { get; set; }
        public bool? Remake { get; set; }
        public string SpecialInstructions { get; set; }
    }

    public class CaseToothDto
    {
        public string ToothNumber { get; set; }
        public string ToothType { get; set; }
        public string ShadeVendor { get; set; }
        public string Color { get; set; }
        public string MillingColor { get; set; }
        public string TissueShade { get; set; }
        public int Quantity { get; set; }
        public string Notes { get; set; }
    }
}
