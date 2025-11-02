# CADCAM Work Management System

A comprehensive web-based management system for dental laboratory work, supporting SOLIDEX, 3D PRINT, and ZEST case types.

## Features

### Core Functionality
- **Case Management**: Create, track, and manage dental cases across all work types
- **Dashboard Analytics**: View statistics and charts with optimized data loading (prevents infinite chart length)
- **Search & Filter**: Quickly find cases by patient name, case number, status, or work type
- **Pagination**: Efficiently browse through large case lists with configurable page sizes (max 100 items per page)
- **Multi-Location Support**: Track cases across HV (Hudson Valley), NYC (New York City), and transfer locations

### Work Types Supported
1. **SOLIDEX**: General dental prosthetic work including crowns, bridges, implants, veneers, inlays, onlays, and dentures
2. **3D PRINT**: 3D printing related work including surgical guides, models, and custom implants
3. **ZEST**: Co-Cr alloy related specialized work

## Technology Stack

- **Frontend**: Blazor Server (ASP.NET Core 3.1)
- **Backend**: ASP.NET Core Web API
- **Database**: MySQL 8.0+
- **ORM**: Entity Framework Core with Pomelo MySQL provider
- **Authentication**: ASP.NET Core Identity

## Database Configuration

The application connects to MySQL with the following configuration:

```json
{
  "ConnectionStrings": {
    "DefaultConnection": "Server=127.0.0.1;Port=3306;Database=u359033001_CADCAM_WORK;User=u359033001_CADCAM_WORK;Password=Creo$10001;"
  }
}
```

## Project Structure

```
Lucent/
├── Controllers/
│   ├── CasesController.cs      # Case management API endpoints
│   └── DashboardController.cs  # Dashboard statistics API
├── Models/
│   ├── Case.cs                 # Main case entity
│   ├── CaseDetail.cs           # Case details (work-type specific)
│   ├── CaseTooth.cs            # Tooth information
│   ├── CaseNote.cs             # Case notes and comments
│   ├── WorkType.cs             # Work type enum
│   ├── CaseStatus.cs           # Case status enum
│   ├── Location.cs             # Location enum
│   └── DTOs/                   # Data Transfer Objects
├── Pages/
│   ├── Index.razor             # Home page
│   ├── Dashboard.razor         # Dashboard with statistics
│   ├── CaseList.razor          # Case listing with filters
│   └── CaseCreate.razor        # Create new case
├── Data/
│   └── ApplicationDbContext.cs # EF Core database context
└── appsettings.json            # Application configuration
```

## Key Features Implementation

### Chart Optimization
To prevent charts from becoming infinitely long:
- Dashboard shows only last 6 months of data
- Maximum of 100 data points per chart
- Recent cases limited to 10 items
- Configured via `appsettings.json`:
  ```json
  "AppSettings": {
    "MaxChartDataPoints": 100,
    "DefaultPageSize": 20,
    "MaxPageSize": 100
  }
  ```

### Pagination
All case lists implement server-side pagination:
- Default page size: 20 items
- Maximum page size: 100 items (enforced server-side)
- Includes total count and page information in response headers

### Case Status Flow
```
Pending → InProgress → Completed → Shipped
         ↓
       OnHold
         ↓
      Cancelled
```

## API Endpoints

### Cases API (`/api/Cases`)
- `GET /api/Cases` - Get paginated case list with filters
  - Query params: `page`, `pageSize`, `searchTerm`, `status`, `workType`, `startDate`, `endDate`
- `GET /api/Cases/{id}` - Get case details with all related data
- `POST /api/Cases` - Create new case
- `PUT /api/Cases/{id}` - Update case
- `PUT /api/Cases/{id}/status` - Update case status
- `DELETE /api/Cases/{id}` - Delete case
- `POST /api/Cases/{id}/notes` - Add note to case

### Dashboard API (`/api/Dashboard`)
- `GET /api/Dashboard/stats` - Get dashboard statistics and charts

## Database Schema

### Cases Table
- Primary entity for all case types
- Includes common fields: case number, patient, lab, dates, status
- Indexed on: case number (unique), patient name, order date, status, work type

### CaseDetails Table
- Stores work-type specific details
- SOLIDEX: Combo, LD, Pan, Design, Nesting
- 3D PRINT: Print Type, Implant Type, Transparency, Note
- ZEST: CAD/EMAX, MC/IOFL, FC, AH, CONTACTS, OCC, Disk Type, E-Max Color

### CaseTeeth Table
- Stores tooth-specific information
- Supports FDI tooth numbering (11-18, 21-28, 31-38, 41-48)
- Includes shade vendor, color, milling color, tissue shade

### CaseNotes Table
- Stores notes and comments for cases
- Includes note type, subject, content, created by, created at

## Installation & Setup

### Prerequisites
- .NET Core 3.1 SDK
- MySQL 8.0 or higher
- Visual Studio 2019+ or VS Code

### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Lucent
   ```

2. **Restore NuGet packages**
   ```bash
   dotnet restore
   ```

3. **Update database connection** (if needed)
   Edit `appsettings.json` to match your MySQL configuration

4. **Create database and run migrations**
   ```bash
   dotnet ef migrations add InitialCreate --project Lucent
   dotnet ef database update --project Lucent
   ```

5. **Run the application**
   ```bash
   dotnet run --project Lucent
   ```

6. **Access the application**
   Open browser and navigate to: `https://localhost:5001`

## Default User Account

After running migrations, you'll need to register the first user through the application's registration page.

For development, you can disable email confirmation in `Startup.cs`:
```csharp
options.SignIn.RequireConfirmedAccount = false;
```

## Configuration

### Application Settings
Located in `appsettings.json`:

```json
{
  "AppSettings": {
    "MaxChartDataPoints": 100,    // Maximum data points in charts
    "DefaultPageSize": 20,         // Default items per page
    "MaxPageSize": 100            // Maximum items per page
  }
}
```

### Identity Settings
Password requirements can be adjusted in `Startup.cs`:
```csharp
options.Password.RequireDigit = true;
options.Password.RequiredLength = 6;
options.Password.RequireNonAlphanumeric = false;
options.Password.RequireUppercase = false;
options.Password.RequireLowercase = false;
```

## Deployment

### Azure App Service
1. Create Azure App Service (Linux, .NET Core 3.1)
2. Create Azure Database for MySQL
3. Update connection string in Azure App Service configuration
4. Deploy using Azure DevOps or GitHub Actions

### Docker
```dockerfile
FROM mcr.microsoft.com/dotnet/aspnet:3.1
COPY bin/Release/netcoreapp3.1/publish/ /app
WORKDIR /app
ENTRYPOINT ["dotnet", "Lucent.dll"]
```

## Development Notes

### Adding New Work Type
1. Update `WorkType.cs` enum
2. Add specific fields to `CaseDetail.cs`
3. Update `CaseCreate.razor` with new form section
4. Add new card to `Index.razor`

### Chart Integration
The dashboard is ready for Chart.js integration via JavaScript interop. Current implementation uses Bootstrap progress bars and tables.

To add Chart.js:
1. Add Chart.js library to `_Host.cshtml`
2. Implement JavaScript interop in `Dashboard.razor`
3. Call JS functions in `OnAfterRenderAsync`

## Security Considerations

- All API endpoints require authentication via `[Authorize]` attribute
- SQL injection prevented by EF Core parameterized queries
- Password hashing handled by ASP.NET Core Identity
- HTTPS enforced in production

## Performance Optimizations

- **Server-side pagination**: Prevents loading entire datasets
- **Indexed database fields**: Fast queries on commonly searched fields
- **Limited chart data**: Only last 6 months shown on dashboard
- **Async/await**: All database operations are asynchronous
- **EF Core tracking**: Disabled for read-only queries where appropriate

## Troubleshooting

### Database Connection Issues
- Verify MySQL server is running
- Check connection string in `appsettings.json`
- Ensure database user has proper permissions
- Test connection using MySQL Workbench or CLI

### Migration Issues
```bash
# Reset migrations
dotnet ef database drop --project Lucent
dotnet ef migrations remove --project Lucent
dotnet ef migrations add InitialCreate --project Lucent
dotnet ef database update --project Lucent
```

### Build Errors
```bash
# Clean and rebuild
dotnet clean
dotnet restore
dotnet build
```

## License

Copyright © 2024 LUCENT. All rights reserved.

## Support

For issues and questions, please contact the development team.

---

**Version**: 1.0.0
**Last Updated**: 2024-11-02
**Built with**: ASP.NET Core 3.1, Blazor Server, MySQL
