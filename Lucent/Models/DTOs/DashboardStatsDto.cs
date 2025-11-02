using System.Collections.Generic;

namespace Lucent.Models.DTOs
{
    public class DashboardStatsDto
    {
        public int TotalCases { get; set; }
        public int PendingCases { get; set; }
        public int InProgressCases { get; set; }
        public int CompletedCases { get; set; }
        public int SolidexCases { get; set; }
        public int Print3DCases { get; set; }
        public int ZestCases { get; set; }

        public List<ChartDataPoint> CasesByStatus { get; set; }
        public List<ChartDataPoint> CasesByWorkType { get; set; }
        public List<ChartDataPoint> CasesByMonth { get; set; }
        public List<RecentCaseDto> RecentCases { get; set; }
    }

    public class ChartDataPoint
    {
        public string Label { get; set; }
        public int Value { get; set; }
        public string Color { get; set; }
    }

    public class RecentCaseDto
    {
        public int CaseId { get; set; }
        public string CaseNumber { get; set; }
        public string PatientName { get; set; }
        public string WorkType { get; set; }
        public string Status { get; set; }
        public string OrderDate { get; set; }
    }
}
