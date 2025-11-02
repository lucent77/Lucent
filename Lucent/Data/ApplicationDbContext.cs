using System;
using System.Collections.Generic;
using System.Text;
using Microsoft.AspNetCore.Identity.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore;
using Lucent.Models;

namespace Lucent.Data
{
    public class ApplicationDbContext : IdentityDbContext
    {
        public ApplicationDbContext(DbContextOptions<ApplicationDbContext> options)
            : base(options)
        {
        }

        public DbSet<Case> Cases { get; set; }
        public DbSet<CaseDetail> CaseDetails { get; set; }
        public DbSet<CaseTooth> CaseTeeth { get; set; }
        public DbSet<CaseNote> CaseNotes { get; set; }

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            base.OnModelCreating(modelBuilder);

            // Configure Case entity
            modelBuilder.Entity<Case>(entity =>
            {
                entity.HasIndex(e => e.CaseNumber).IsUnique();
                entity.HasIndex(e => e.PatientName);
                entity.HasIndex(e => e.OrderDate);
                entity.HasIndex(e => e.Status);
                entity.HasIndex(e => e.WorkType);

                entity.Property(e => e.CreatedAt)
                    .HasDefaultValueSql("CURRENT_TIMESTAMP");
                entity.Property(e => e.UpdatedAt)
                    .HasDefaultValueSql("CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            });

            // Configure CaseDetail entity
            modelBuilder.Entity<CaseDetail>(entity =>
            {
                entity.HasOne(d => d.Case)
                    .WithMany(p => p.CaseDetails)
                    .HasForeignKey(d => d.CaseId)
                    .OnDelete(DeleteBehavior.Cascade);

                entity.Property(e => e.CreatedAt)
                    .HasDefaultValueSql("CURRENT_TIMESTAMP");
                entity.Property(e => e.UpdatedAt)
                    .HasDefaultValueSql("CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            });

            // Configure CaseTooth entity
            modelBuilder.Entity<CaseTooth>(entity =>
            {
                entity.HasOne(d => d.Case)
                    .WithMany(p => p.CaseTeeth)
                    .HasForeignKey(d => d.CaseId)
                    .OnDelete(DeleteBehavior.Cascade);

                entity.HasIndex(e => e.ToothNumber);

                entity.Property(e => e.CreatedAt)
                    .HasDefaultValueSql("CURRENT_TIMESTAMP");
            });

            // Configure CaseNote entity
            modelBuilder.Entity<CaseNote>(entity =>
            {
                entity.HasOne(d => d.Case)
                    .WithMany(p => p.CaseNotes)
                    .HasForeignKey(d => d.CaseId)
                    .OnDelete(DeleteBehavior.Cascade);

                entity.HasIndex(e => e.CreatedAt);

                entity.Property(e => e.CreatedAt)
                    .HasDefaultValueSql("CURRENT_TIMESTAMP");
            });
        }
    }
}
