using System;
using System.Windows.Forms;
using DberUpdater;

namespace Sequential_File_Downloader;

internal static class Program
{
	[STAThread]
	private static void Main()
	{
		Application.EnableVisualStyles();
		Application.SetCompatibleTextRenderingDefault(false);
		Application.Run((Form)(object)new FormDownloader());
	}
}
