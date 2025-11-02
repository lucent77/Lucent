using System;

namespace Lucent.Models.DTOs
{
    public class CaseListDto
    {
        public int CaseId { get; set; }
        public string CaseNumber { get; set; }
        public string PatientName { get; set; }
        public string LabName { get; set; }
        public string AccountName { get; set; }
        public WorkType WorkType { get; set; }
        public CaseStatus Status { get; set; }
        public Location Location { get; set; }
        public string CaseType { get; set; }
        public DateTime OrderDate { get; set; }
        public DateTime? DueDate { get; set; }
        public DateTime? ShipDate { get; set; }
        public string TrackingNumber { get; set; }
    }
}
