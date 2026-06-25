<#
.SYNOPSIS
    Send raw binary file directly to Windows printer, bypassing the printer driver.
    Uses Windows Spooler API with RAW data type.
.PARAMETER PrinterName
    The name of the printer as shown in Windows Settings.
.PARAMETER FilePath
    Path to the binary file containing raw ESC/POS data.
#>
param(
    [Parameter(Mandatory=$true)]
    [string]$PrinterName,

    [Parameter(Mandatory=$true)]
    [string]$FilePath
)

Add-Type @"
using System;
using System.IO;
using System.Runtime.InteropServices;

public class RawPrinterHelper
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA
    {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", CharSet = CharSet.Ansi, SetLastError = true)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", CharSet = CharSet.Ansi, SetLastError = true)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static bool SendFileToPrinter(string szPrinterName, string szFileName)
    {
        IntPtr hPrinter;
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "Nota Receipt";
        di.pDataType = "RAW";

        if (!OpenPrinter(szPrinterName.Normalize(), out hPrinter, IntPtr.Zero))
        {
            Console.Error.WriteLine("ERROR: Cannot open printer '" + szPrinterName + "'");
            return false;
        }

        if (!StartDocPrinter(hPrinter, 1, di))
        {
            Console.Error.WriteLine("ERROR: StartDocPrinter failed");
            ClosePrinter(hPrinter);
            return false;
        }

        if (!StartPagePrinter(hPrinter))
        {
            Console.Error.WriteLine("ERROR: StartPagePrinter failed");
            EndDocPrinter(hPrinter);
            ClosePrinter(hPrinter);
            return false;
        }

        FileStream fs = new FileStream(szFileName, FileMode.Open);
        BinaryReader br = new BinaryReader(fs);
        byte[] bytes = br.ReadBytes((int)fs.Length);
        IntPtr pUnmanagedBytes = Marshal.AllocCoTaskMem((int)fs.Length);
        Marshal.Copy(bytes, 0, pUnmanagedBytes, (int)fs.Length);

        int dwWritten;
        bool bSuccess = WritePrinter(hPrinter, pUnmanagedBytes, (int)fs.Length, out dwWritten);

        Marshal.FreeCoTaskMem(pUnmanagedBytes);
        br.Close();
        fs.Close();

        EndPagePrinter(hPrinter);
        EndDocPrinter(hPrinter);
        ClosePrinter(hPrinter);

        if (bSuccess)
        {
            Console.WriteLine("OK: Sent " + dwWritten + " bytes to printer");
        }
        else
        {
            Console.Error.WriteLine("ERROR: WritePrinter failed");
        }

        return bSuccess;
    }
}
"@

if (-not (Test-Path $FilePath)) {
    Write-Error "File not found: $FilePath"
    exit 1
}

$result = [RawPrinterHelper]::SendFileToPrinter($PrinterName, $FilePath)

if ($result) {
    Write-Output "SUCCESS"
    exit 0
} else {
    Write-Error "FAILED to print"
    exit 1
}
