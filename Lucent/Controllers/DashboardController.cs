using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Lucent.Data;
using Lucent.Models;
using Lucent.Models.DTOs;

namespace Lucent.Controllers
{
    [Authorize]
    [ApiController]
    [Route("api/[controller]")]
    public class DashboardController : ControllerBase
    {
        private readonly ApplicationDbContext _context;

        public DashboardController(ApplicationDbContext context)
        {
            _context = context;
        }

        // GET: api/Dashboard/stats
        [HttpGet("stats")]
        public async Task<ActionResult<DashboardStatsDto>> GetDashboardStats()
        {
            var now = DateTime.Now;
            var firstDayOfMonth = new DateTime(now.Year, now.Month, 1);
            var lastDayOfMonth = firstDayOfMonth.AddMonths(1).AddDays(-1);

            var totalCases = await _context.Cases.CountAsync();
            var pendingCases = await _context.Cases.CountAsync(c => c.Status == CaseStatus.Pending);
            var inProgressCases = await _context.Cases.CountAsync(c => c.Status == CaseStatus.InProgress);
            var completedCases = await _context.Cases.CountAsync(c => c.Status == CaseStatus.Completed);

            var solidexCases = await _context.Cases.CountAsync(c => c.WorkType == WorkType.SOLIDEX);
            var print3dCases = await _context.Cases.CountAsync(c => c.WorkType == WorkType.PRINT3D);
            var zestCases = await _context.Cases.CountAsync(c => c.WorkType == WorkType.ZEST);

            // Cases by status (for pie chart)
            var casesByStatus = new List<ChartDataPoint>
            {
                new ChartDataPoint { Label = "Pending", Value = pendingCases, Color = "#ffc107" },
                new ChartDataPoint { Label = "In Progress", Value = inProgressCases, Color = "#17a2b8" },
                new ChartDataPoint { Label = "Completed", Value = completedCases, Color = "#28a745" }
            };

            // Cases by work type (for pie chart)
            var casesByWorkType = new List<ChartDataPoint>
            {
                new ChartDataPoint { Label = "SOLIDEX", Value = solidexCases, Color = "#007bff" },
                new ChartDataPoint { Label = "3D PRINT", Value = print3dCases, Color = "#6f42c1" },
                new ChartDataPoint { Label = "ZEST", Value = zestCases, Color = "#fd7e14" }
            };

            // Cases by month (last 6 months, limited to prevent infinite chart length)
            var sixMonthsAgo = now.AddMonths(-6);
            var casesByMonthData = await _context.Cases
                .Where(c => c.OrderDate >= sixMonthsAgo)
                .GroupBy(c => new { c.OrderDate.Year, c.OrderDate.Month })
                .Select(g => new
                {
                    Year = g.Key.Year,
                    Month = g.Key.Month,
                    Count = g.Count()
                })
                .OrderBy(x => x.Year).ThenBy(x => x.Month)
                .ToListAsync();

            var casesByMonth = casesByMonthData.Select(x => new ChartDataPoint
            {
                Label = $"{x.Year}-{x.Month:D2}",
                Value = x.Count,
                Color = "#007bff"
            }).ToList();

            // Recent cases (limited to 10 to prevent excessive data)
            var recentCasesData = await _context.Cases
                .OrderByDescending(c => c.OrderDate)
                .Take(10)
                .Select(c => new RecentCaseDto
                {
                    CaseId = c.CaseId,
                    CaseNumber = c.CaseNumber,
                    PatientName = c.PatientName,
                    WorkType = c.WorkType.ToString(),
                    Status = c.Status.ToString(),
                    OrderDate = c.OrderDate.ToString("yyyy-MM-dd")
                })
                .ToListAsync();

            var stats = new DashboardStatsDto
            {
                TotalCases = totalCases,
                PendingCases = pendingCases,
                InProgressCases = inProgressCases,
                CompletedCases = completedCases,
                SolidexCases = solidexCases,
                Print3DCases = print3dCases,
                ZestCases = zestCases,
                CasesByStatus = casesByStatus,
                CasesByWorkType = casesByWorkType,
                CasesByMonth = casesByMonth,
                RecentCases = recentCasesData
            };

            return Ok(stats);
        }
    }
}
