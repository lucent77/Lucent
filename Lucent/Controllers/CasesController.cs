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
    public class CasesController : ControllerBase
    {
        private readonly ApplicationDbContext _context;

        public CasesController(ApplicationDbContext context)
        {
            _context = context;
        }

        // GET: api/Cases
        [HttpGet]
        public async Task<ActionResult<IEnumerable<CaseListDto>>> GetCases(
            [FromQuery] int page = 1,
            [FromQuery] int pageSize = 20,
            [FromQuery] string searchTerm = "",
            [FromQuery] CaseStatus? status = null,
            [FromQuery] WorkType? workType = null,
            [FromQuery] DateTime? startDate = null,
            [FromQuery] DateTime? endDate = null)
        {
            if (pageSize > 100) pageSize = 100; // Prevent excessive data loading

            var query = _context.Cases.AsQueryable();

            // Apply filters
            if (!string.IsNullOrEmpty(searchTerm))
            {
                query = query.Where(c =>
                    c.CaseNumber.Contains(searchTerm) ||
                    c.PatientName.Contains(searchTerm) ||
                    c.LabName.Contains(searchTerm));
            }

            if (status.HasValue)
            {
                query = query.Where(c => c.Status == status.Value);
            }

            if (workType.HasValue)
            {
                query = query.Where(c => c.WorkType == workType.Value);
            }

            if (startDate.HasValue)
            {
                query = query.Where(c => c.OrderDate >= startDate.Value);
            }

            if (endDate.HasValue)
            {
                query = query.Where(c => c.OrderDate <= endDate.Value);
            }

            var totalCount = await query.CountAsync();

            var cases = await query
                .OrderByDescending(c => c.OrderDate)
                .Skip((page - 1) * pageSize)
                .Take(pageSize)
                .Select(c => new CaseListDto
                {
                    CaseId = c.CaseId,
                    CaseNumber = c.CaseNumber,
                    PatientName = c.PatientName,
                    LabName = c.LabName,
                    AccountName = c.AccountName,
                    WorkType = c.WorkType,
                    Status = c.Status,
                    Location = c.Location,
                    CaseType = c.CaseType,
                    OrderDate = c.OrderDate,
                    DueDate = c.DueDate,
                    ShipDate = c.ShipDate,
                    TrackingNumber = c.TrackingNumber
                })
                .ToListAsync();

            Response.Headers.Add("X-Total-Count", totalCount.ToString());
            Response.Headers.Add("X-Page", page.ToString());
            Response.Headers.Add("X-Page-Size", pageSize.ToString());

            return Ok(cases);
        }

        // GET: api/Cases/5
        [HttpGet("{id}")]
        public async Task<ActionResult<Case>> GetCase(int id)
        {
            var caseItem = await _context.Cases
                .Include(c => c.CaseDetails)
                .Include(c => c.CaseTeeth)
                .Include(c => c.CaseNotes)
                .FirstOrDefaultAsync(c => c.CaseId == id);

            if (caseItem == null)
            {
                return NotFound();
            }

            return Ok(caseItem);
        }

        // POST: api/Cases
        [HttpPost]
        public async Task<ActionResult<Case>> CreateCase(CaseCreateDto dto)
        {
            var caseItem = new Case
            {
                CaseNumber = dto.CaseNumber,
                PatientName = dto.PatientName,
                LabName = dto.LabName,
                AccountName = dto.AccountName,
                WorkType = dto.WorkType,
                Location = dto.Location,
                CaseType = dto.CaseType,
                Status = CaseStatus.Pending,
                OrderDate = DateTime.Now,
                DueDate = dto.DueDate,
                AppointmentDate = dto.AppointmentDate,
                Instructions = dto.Instructions,
                Preferences = dto.Preferences,
                CreatedAt = DateTime.Now,
                UpdatedAt = DateTime.Now,
                CreatedBy = User.Identity.Name
            };

            _context.Cases.Add(caseItem);
            await _context.SaveChangesAsync();

            // Add case details if provided
            if (dto.Details != null)
            {
                var caseDetail = new CaseDetail
                {
                    CaseId = caseItem.CaseId,
                    Combo = dto.Details.Combo,
                    LD = dto.Details.LD,
                    Pan = dto.Details.Pan,
                    Design = dto.Details.Design,
                    Nesting = dto.Details.Nesting,
                    PrintType = dto.Details.PrintType,
                    ImplantType = dto.Details.ImplantType,
                    Transparency = dto.Details.Transparency,
                    Note = dto.Details.Note,
                    CADEMAX = dto.Details.CADEMAX,
                    MCIOFL = dto.Details.MCIOFL,
                    FC = dto.Details.FC,
                    AH = dto.Details.AH,
                    CONTACTS = dto.Details.CONTACTS,
                    OCC = dto.Details.OCC,
                    DiskType = dto.Details.DiskType,
                    EmaxColor = dto.Details.EmaxColor,
                    ZestType = dto.Details.ZestType,
                    Remake = dto.Details.Remake,
                    SpecialInstructions = dto.Details.SpecialInstructions,
                    CreatedAt = DateTime.Now,
                    UpdatedAt = DateTime.Now
                };
                _context.CaseDetails.Add(caseDetail);
            }

            // Add teeth if provided
            if (dto.Teeth != null && dto.Teeth.Any())
            {
                foreach (var tooth in dto.Teeth)
                {
                    var caseTooth = new CaseTooth
                    {
                        CaseId = caseItem.CaseId,
                        ToothNumber = tooth.ToothNumber,
                        ToothType = tooth.ToothType,
                        ShadeVendor = tooth.ShadeVendor,
                        Color = tooth.Color,
                        MillingColor = tooth.MillingColor,
                        TissueShade = tooth.TissueShade,
                        Quantity = tooth.Quantity,
                        Notes = tooth.Notes,
                        CreatedAt = DateTime.Now
                    };
                    _context.CaseTeeth.Add(caseTooth);
                }
            }

            await _context.SaveChangesAsync();

            return CreatedAtAction(nameof(GetCase), new { id = caseItem.CaseId }, caseItem);
        }

        // PUT: api/Cases/5
        [HttpPut("{id}")]
        public async Task<IActionResult> UpdateCase(int id, Case caseItem)
        {
            if (id != caseItem.CaseId)
            {
                return BadRequest();
            }

            caseItem.UpdatedAt = DateTime.Now;
            _context.Entry(caseItem).State = EntityState.Modified;

            try
            {
                await _context.SaveChangesAsync();
            }
            catch (DbUpdateConcurrencyException)
            {
                if (!CaseExists(id))
                {
                    return NotFound();
                }
                else
                {
                    throw;
                }
            }

            return NoContent();
        }

        // PUT: api/Cases/5/status
        [HttpPut("{id}/status")]
        public async Task<IActionResult> UpdateCaseStatus(int id, [FromBody] CaseStatus status)
        {
            var caseItem = await _context.Cases.FindAsync(id);
            if (caseItem == null)
            {
                return NotFound();
            }

            caseItem.Status = status;
            caseItem.UpdatedAt = DateTime.Now;

            await _context.SaveChangesAsync();

            return NoContent();
        }

        // DELETE: api/Cases/5
        [HttpDelete("{id}")]
        public async Task<IActionResult> DeleteCase(int id)
        {
            var caseItem = await _context.Cases.FindAsync(id);
            if (caseItem == null)
            {
                return NotFound();
            }

            _context.Cases.Remove(caseItem);
            await _context.SaveChangesAsync();

            return NoContent();
        }

        // POST: api/Cases/5/notes
        [HttpPost("{id}/notes")]
        public async Task<ActionResult<CaseNote>> AddCaseNote(int id, [FromBody] CaseNote note)
        {
            var caseItem = await _context.Cases.FindAsync(id);
            if (caseItem == null)
            {
                return NotFound();
            }

            note.CaseId = id;
            note.CreatedBy = User.Identity.Name;
            note.CreatedAt = DateTime.Now;

            _context.CaseNotes.Add(note);
            await _context.SaveChangesAsync();

            return CreatedAtAction(nameof(GetCase), new { id = id }, note);
        }

        private bool CaseExists(int id)
        {
            return _context.Cases.Any(e => e.CaseId == id);
        }
    }
}
