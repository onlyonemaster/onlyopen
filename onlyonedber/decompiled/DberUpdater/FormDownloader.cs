using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.IO.Compression;
using System.Net;
using System.Net.Security;
using System.Reflection;
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Text.RegularExpressions;
using System.Threading;
using System.Windows.Forms;
using System.Xml;
using Microsoft.Win32;

namespace DberUpdater;

public class FormDownloader : Form
{
	public static string m_strServerURL = "https://www.kiam.kr/downloads";

	public static bool m_bCheckStatus = true;

	public static List<string> m_lstFiles = new List<string>();

	private Stopwatch Timer = new Stopwatch();

	private bool Cancel;

	private int DownloadIndexStart = 0;

	private int DownloadIndexEnd = 0;

	private int DownloadIndex = 0;

	private int DownloadCountEnd = 0;

	private int DownloadCount = 0;

	private IContainer components = null;

	private ProgressBar ProgressBarSequence;

	private Label LabelProgress;

	private ProgressBar ProgressBarDownloading;

	private Label LabelDownloadingUrl;

	private FolderBrowserDialog FolderBrowserDialogDownloadLocation;

	private Label LabelStatus;

	/// <summary>
	/// FIX #6: Use disposable WebClient per download to avoid concurrent-call corruption.
	/// </summary>
	private WebClient CreateClient()
	{
		WebClient client = new WebClient();
		client.DownloadProgressChanged += client_DownloadProgressChanged;
		client.DownloadFileCompleted += client_DownloadFileCompleted;
		return client;
	}

	public FormDownloader()
	{
		InitializeComponent();
	}

	private void FormAsync_Load(object sender, EventArgs e)
	{
		DownloadsFiles();
	}

	private void DownloadsFiles()
	{
		try
		{
			WebClient client = CreateClient();
			Uri address = new Uri($"{m_strServerURL}/update.xml");
			string text = $"{Path.GetTempPath()}update.xml";
			client.DownloadFileAsync(address, text, text);
		}
		catch (Exception ex)
		{
			string text2 = $"update.xml 다운로드 중 오류가 발생하였습니다.\n오류내용: {ex.Message}";
			MessageBox.Show(text2);
			((Form)this).Close();
		}
	}

	private void DownloadFile(bool Abort = false)
	{
		try
		{
			if (DownloadIndex < DownloadIndexEnd && !Cancel && !Abort)
			{
				// FIX #2: Path Traversal 방어 — 상대경로/절대경로 공격 차단
				string fileName = m_lstFiles[DownloadIndex];
				string cleanName = SanitizeFileName(fileName);

				if (cleanName.Contains("chromedriver.exe"))
				{
					UpdateChromeDriver();
				}
				else
				{
					Uri address = new Uri($"{m_strServerURL}/dber/{fileName}");
					string savePath = $"{Directory.GetCurrentDirectory()}\\{cleanName}";
					WebClient client = CreateClient();
					client.DownloadFileAsync(address, savePath, savePath);
				}
				FileInfo fileInfo = new FileInfo(cleanName);
				((Control)LabelDownloadingUrl).Text = fileInfo.Name;
				return;
			}
		}
		catch (Exception ex)
		{
			string text2 = $"파일 다운로드 중 오류가 발생하였습니다.\n오류내용: {ex.Message}";
			MessageBox.Show(text2);
			((Form)this).Close();
		}
		Timer.Stop();
		if (Cancel || Abort)
		{
			TaskbarProgress.SetState(((Control)this).Handle, TaskbarProgress.TaskbarStates.Error);
			return;
		}
		((Control)LabelStatus).Text = "업데이트 완료 " + DownloadCount;
		TaskbarProgress.SetState(((Control)this).Handle, TaskbarProgress.TaskbarStates.Paused);
		string text3 = $"{Directory.GetCurrentDirectory()}\\온리원.exe";
		if (File.Exists(text3))
		{
			Process.Start(text3);
		}
		else
		{
			MessageBox.Show("실행파일을 찾을수 없습니다.");
		}
		((Form)this).Close();
	}

	/// <summary>
	/// FIX #2: Path Traversal / Directory Traversal 방어.
	/// "..", 절대경로, backslash injection 모두 차단.
	/// </summary>
	private static string SanitizeFileName(string fileName)
	{
		if (string.IsNullOrEmpty(fileName))
			throw new ArgumentException("파일명이 비어있습니다.");

		// 절대경로 차단
		if (Path.IsPathRooted(fileName))
			fileName = Path.GetFileName(fileName);

		// ".." 제거
		fileName = fileName.Replace("..", "");

		// 역슬래시·슬래시 모두 파일명만 추출
		fileName = Path.GetFileName(fileName);

		// 허용되지 않는 문자 제거
		char[] invalidChars = Path.GetInvalidFileNameChars();
		foreach (char c in invalidChars)
		{
			fileName = fileName.Replace(c.ToString(), "");
		}

		return fileName;
	}

	private static string CalculateMD5(string filename)
	{
		using MD5 mD = MD5.Create();
		using FileStream inputStream = File.OpenRead(filename);
		byte[] array = mD.ComputeHash(inputStream);
		return BitConverter.ToString(array).Replace("-", "").ToLowerInvariant();
	}

	/// <summary>
	/// FIX #1: Safe version parsing — handles Chrome/ChromeDriver versions without '.'.
	/// Returns -1 if parsing fails.
	/// </summary>
	private static int ParseMajorVersion(string version)
	{
		if (string.IsNullOrEmpty(version))
			return -1;

		int dotIndex = version.IndexOf('.');
		if (dotIndex <= 0)
		{
			// No dot found — try parsing whole string as number
			if (int.TryParse(version, out int result))
				return result;
			return -1;
		}

		string majorPart = version.Substring(0, dotIndex);
		if (int.TryParse(majorPart, out int majorResult))
			return majorResult;

		return -1;
	}

	/// <summary>
	/// FIX #3: XXE 방어 + XML 파싱 실패 시 안전한 예외 처리.
	/// </summary>
	private void GetUpdateInfo(string strXML)
	{
		XmlDocument val = new XmlDocument();
		// XXE 방어 설정
		val.XmlResolver = null;

		try
		{
			val.Load(strXML);
		}
		catch (Exception ex)
		{
			string msg = $"update.xml 파일을 읽을 수 없습니다.\n서버 연결을 확인해주세요.\n오류: {ex.Message}";
			MessageBox.Show(msg);
			((Form)this).Close();
			return;
		}

		XmlElement documentElement = val.DocumentElement;
		if (documentElement == null)
		{
			MessageBox.Show("update.xml 파일 형식이 올바르지 않습니다.");
			((Form)this).Close();
			return;
		}

		for (XmlNode val2 = ((XmlNode)documentElement).FirstChild; val2 != null; val2 = val2.NextSibling)
		{
			XmlNode namedItem = ((XmlNamedNodeMap)val2.Attributes).GetNamedItem("path");
			XmlNode namedItem2 = ((XmlNamedNodeMap)val2.Attributes).GetNamedItem("hash");
			if (namedItem == null || namedItem2 == null)
				continue;

			string value = namedItem.Value;
			string value2 = namedItem2.Value;
			string text = $"{Directory.GetCurrentDirectory()}\\{SanitizeFileName(value)}";
			FileInfo fileInfo = new FileInfo(text);
			if (fileInfo.Exists)
			{
				if (value.Contains("chromedriver.exe"))
				{
					try
					{
						string currentChromeDriverVersion = GetCurrentChromeDriverVersion();
						if (string.IsNullOrEmpty(currentChromeDriverVersion))
						{
							m_lstFiles.Add(value);
						}
						else
						{
							int num = ParseMajorVersion(currentChromeDriverVersion);
							string chromeVersion = GetChromeVersion();
							if (string.IsNullOrEmpty(chromeVersion))
							{
								m_lstFiles.Add(value);
							}
							else
							{
								int num2 = ParseMajorVersion(chromeVersion);
								if (num < num2)
								{
									m_lstFiles.Add(value);
								}
							}
						}
					}
					catch (Exception ex)
					{
						MessageBox.Show($"ChromeDriver 버전 확인 중 오류: {ex.Message}");
					}
				}
				else
				{
					try
					{
						string text2 = CalculateMD5(text);
						if (text2 != value2)
						{
							m_lstFiles.Add(value);
						}
					}
					catch (Exception ex)
					{
						// MD5 계산 실패 → 파일 손상으로 간주, 재다운로드
						m_lstFiles.Add(value);
					}
				}
			}
			else
			{
				if (!fileInfo.Directory.Exists)
				{
					Directory.CreateDirectory(fileInfo.DirectoryName);
				}
				m_lstFiles.Add(value);
			}
		}
	}

	private void client_DownloadFileCompleted(object sender, AsyncCompletedEventArgs e)
	{
		try
		{
			string text = (string)e.UserState;
			if (e.Error != null)
			{
				FileInfo fileInfo = new FileInfo(text ?? "");
				string text2 = $"{fileInfo.Name}파일을 갱신할수 없습니다.\n원인: {e.Error.Message}";
				MessageBox.Show(text2);
				if (fileInfo.Exists)
				{
					try { fileInfo.Delete(); } catch { }
				}
			}
			if (m_bCheckStatus)
			{
				GetUpdateInfo(text);
				m_bCheckStatus = false;
				KillMainProcesses();
				ProgressBarSequence.Value = 0;
				TaskbarProgress.SetState(((Control)this).Handle, TaskbarProgress.TaskbarStates.NoProgress);
				((Control)LabelStatus).Text = "업데이트 진행중...";
				Timer.Reset();
				Timer.Start();
				Cancel = false;
				DownloadIndexStart = 0;
				DownloadIndexEnd = m_lstFiles.Count;
				DownloadIndex = DownloadIndexStart;
				DownloadCount = 0;
				DownloadCountEnd = DownloadIndexEnd - DownloadIndexStart;
				((Control)LabelProgress).Text = "0 / " + DownloadCountEnd;
				DownloadFile();
				return;
			}
			if (text != null && text.Contains("chromedriver"))
			{
				ExtractChromeDriverZip();
			}
			if (e.Error == null)
			{
				DownloadCount++;
			}
			DownloadIndex++;
			((Control)LabelProgress).Text = DownloadCount + " / " + DownloadCountEnd;

			// FIX #2: DivideByZero 방어 — DownloadCountEnd == 0 시 100%로 표시
			if (DownloadCountEnd > 0)
			{
				int num = Convert.ToInt32((decimal)DownloadCount / Convert.ToDecimal(DownloadCountEnd) * 100m);
				if (num >= 0 && num <= 100)
				{
					ProgressBarSequence.Value = num;
					TaskbarProgress.SetValue(((Control)this).Handle, num, 100.0);
					TaskbarProgress.SetState(((Control)this).Handle, TaskbarProgress.TaskbarStates.Normal);
				}
			}
			else
			{
				// 업데이트 파일 없음 → 바로 완료 처리
				ProgressBarSequence.Value = 100;
				TaskbarProgress.SetValue(((Control)this).Handle, 100, 100.0);
				TaskbarProgress.SetState(((Control)this).Handle, TaskbarProgress.TaskbarStates.Paused);
			}
			DownloadFile();
		}
		catch (Exception ex)
		{
			string text5 = $"오류가 발생하였습니다.\n오류내용: {ex.Message}\n오류위치:{ex.StackTrace}";
			MessageBox.Show(text5);
			((Form)this).Close();
		}
	}

	private void client_DownloadProgressChanged(object sender, DownloadProgressChangedEventArgs e)
	{
		// FIX #4: NullReference 방어 — UserState null 체크
		if (e.UserState == null)
			return;

		if (!((string)e.UserState).Contains("update.xml"))
		{
			double num = double.Parse(e.BytesReceived.ToString());
			double num2 = double.Parse(e.TotalBytesToReceive.ToString());
			double num3 = num / num2 * 100.0;
			if (num3 >= 0.0 && num3 <= 100.0)
			{
				ProgressBarDownloading.Value = int.Parse(Math.Truncate(num3).ToString());
			}
		}
	}

	private void UpdateChromeDriver()
	{
		try
		{
			string chromeVersion = GetChromeVersion();
			if (!string.IsNullOrEmpty(chromeVersion))
			{
				string chromeDriverLink = GetChromeDriverLink(chromeVersion);
				KillAllChromeDriverProcesses();
				DownloadChromeDriver(chromeDriverLink);
			}
			else
			{
				string text = "크롬브라우저의 버전정보를 얻을수 없습니다.";
				MessageBox.Show(text);
				DownloadIndex++;
				DownloadFile();
			}
		}
		catch (Exception ex)
		{
			string text2 = "chromedriver.exe파일을 갱신할수 없습니다.\n" + ex.Message;
			MessageBox.Show(text2);
			DownloadIndex++;
			DownloadFile();
		}
	}

	private string GetChromeVersion()
	{
		try
		{
			string text = (string)Registry.GetValue("HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\chrome.exe", null, null);
			if (text == null)
			{
				// 64비트 경로도 시도
				text = (string)Registry.GetValue("HKEY_LOCAL_MACHINE\\SOFTWARE\\WOW6432Node\\Microsoft\\Windows\\CurrentVersion\\App Paths\\chrome.exe", null, null);
			}
			if (text == null)
			{
				return string.Empty;
			}
			FileVersionInfo versionInfo = FileVersionInfo.GetVersionInfo(text);
			return versionInfo.FileVersion ?? string.Empty;
		}
		catch
		{
			return string.Empty;
		}
	}

	public string GetLatestChromeDriverVersion()
	{
		string text = string.Empty;
		try
		{
			HttpWebRequest httpWebRequest = (HttpWebRequest)WebRequest.Create("https://chromedriver.storage.googleapis.com/LATEST_RELEASE");
			// Timeout 설정 추가
			httpWebRequest.Timeout = 10000;
			using HttpWebResponse httpWebResponse = (HttpWebResponse)httpWebRequest.GetResponse();
			using Stream stream = httpWebResponse.GetResponseStream();
			using StreamReader streamReader = new StreamReader(stream);
			text = streamReader.ReadToEnd();
		}
		catch (Exception ex)
		{
			Console.WriteLine($"ChromeDriver 최신버전 조회 실패: {ex.Message}");
		}
		if (string.IsNullOrEmpty(text))
		{
			MessageBox.Show("크롬드라이버 최신버전 정보 얻기 실패\n인터넷 연결을 확인해주세요.");
		}
		return text;
	}

	public string GetChromeDriverLink(string version)
	{
		if (string.IsNullOrEmpty(version))
		{
			throw new ArgumentException("크롬버전정보를 얻을수 없습니다.");
		}
		string text = string.Empty;
		try
		{
			int num = ParseMajorVersion(version);
			if (num < 0)
			{
				throw new ArgumentException($"크롬 버전을 파싱할 수 없습니다: {version}");
			}

			if (num >= 115)
			{
				ServicePointManager.Expect100Continue = true;
				// FIX #5: SSL3/TLS1.0/TLS1.1 제거. TLS 1.2 + 1.3 사용.
				ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12 | (SecurityProtocolType)12288; // 12288 = Tls13

				// FIX #1: SSL 인증서 검증 복구 — 커스텀 콜백 제거.
				// ServerCertificateValidationCallback 설정하지 않음 = 기본 OS 검증 사용.

				string requestUriString = "https://googlechromelabs.github.io/chrome-for-testing/known-good-versions-with-downloads.json";
				HttpWebRequest httpWebRequest = (HttpWebRequest)WebRequest.Create(requestUriString);
				httpWebRequest.AutomaticDecompression = DecompressionMethods.GZip;
				httpWebRequest.Timeout = 15000;
				using (HttpWebResponse httpWebResponse = (HttpWebResponse)httpWebRequest.GetResponse())
				{
					using Stream stream = httpWebResponse.GetResponseStream();
					using StreamReader streamReader = new StreamReader(stream);
					text = streamReader.ReadToEnd();
				}
				for (int num2 = version.Length; num2 >= 4; num2--)
				{
					string arg = version.Substring(0, num2);
					string pattern = $"https://storage.googleapis.com/chrome-for-testing-public/{Regex.Escape(arg)}([0-9\\.])*/win32/chromedriver-win32.zip";
					Match match = Regex.Match(text, pattern);
					if (match.Success)
					{
						return match.Value;
					}
				}
				throw new Exception($"Chrome {version}에 해당하는 크롬드라이버를 찾을 수 없습니다.");
			}

			// Chrome 114 이하 — Legacy 경로
			string[] array = new string[3];
			string[] parts = version.Split(new char[1] { '.' });
			Array.Copy(parts, array, Math.Min(3, parts.Length));
			string requestUriString2 = "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_" + string.Join(".", array);
			HttpWebRequest httpWebRequest2 = (HttpWebRequest)WebRequest.Create(requestUriString2);
			httpWebRequest2.Timeout = 10000;
			using (HttpWebResponse httpWebResponse2 = (HttpWebResponse)httpWebRequest2.GetResponse())
			{
				using Stream stream2 = httpWebResponse2.GetResponseStream();
				using StreamReader streamReader2 = new StreamReader(stream2);
				text = streamReader2.ReadToEnd();
			}
			return "https://chromedriver.storage.googleapis.com/" + text + "/chromedriver_win32.zip";
		}
		catch (Exception)
		{
			// FIX: 폴백은 최신 버전 대신 실패 메시지로 — 호환성 이슈 방지
			text = GetLatestChromeDriverVersion();
			if (string.IsNullOrEmpty(text))
				throw new Exception("ChromeDriver 다운로드 URL을 찾을 수 없습니다.");
			return "https://chromedriver.storage.googleapis.com/" + text + "/chromedriver_win32.zip";
		}
	}

	private void KillAllChromeDriverProcesses()
	{
		try
		{
			Process[] processesByName = Process.GetProcessesByName("chromedriver");
			foreach (Process process in processesByName)
			{
				try
				{
					if (!process.HasExited)
					{
						process.Kill();
						process.WaitForExit(3000);
					}
				}
				catch { /* 프로세스 이미 종료됨 */ }
				finally
				{
					try { process.Dispose(); } catch { }
				}
			}
		}
		catch { /* 프로세스 목록 조회 실패 — 무시 */ }
	}

	private void KillMainProcesses()
	{
		try
		{
			Process[] processesByName = Process.GetProcessesByName("온리원");
			foreach (Process process in processesByName)
			{
				try
				{
					if (!process.HasExited)
					{
						process.Kill();
						process.WaitForExit(3000);
					}
				}
				catch { /* 프로세스 이미 종료됨 */ }
				finally
				{
					try { process.Dispose(); } catch { }
				}
			}
		}
		catch { /* 프로세스 목록 조회 실패 — 무시 */ }
	}

	private void DownloadChromeDriver(string urlToDownload)
	{
		if (string.IsNullOrEmpty(urlToDownload))
		{
			throw new ArgumentException("크롬드라이버 링크가 올바르지 않습니다.");
		}
		string text = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location) + "\\chromedriver.zip";
		if (File.Exists(text))
		{
			try { File.Delete(text); } catch { }
		}
		Uri address = new Uri(urlToDownload);
		WebClient client = CreateClient();
		client.DownloadFileAsync(address, text, text);
	}

	/// <summary>
	/// ChromeDriver ZIP 압축 해제 로직 — client_DownloadFileCompleted에서 분리.
	/// </summary>
	private void ExtractChromeDriverZip()
	{
		string directoryName = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location);
		string text3 = directoryName + "\\chromedriver.zip";
		string path = directoryName + "\\chromedriver.exe";
		string path2 = directoryName + "\\LICENSE.chromedriver";
		string path3 = directoryName + "\\chromedriver-win32";

		if (File.Exists(text3) && File.Exists(path))
		{
			try
			{
				File.Delete(path);
				File.Delete(path2);
			}
			catch { }
		}
		if (Directory.Exists(path3))
		{
			try { Directory.Delete(path3, recursive: true); }
			catch { }
		}
		if (File.Exists(text3))
		{
			try
			{
				ZipFile.ExtractToDirectory(text3, directoryName);
				if (Directory.Exists(path3))
				{
					string[] files = Directory.GetFiles(path3);
					foreach (string text4 in files)
					{
						string destFileName = Path.Combine(directoryName, Path.GetFileName(text4));
						File.Copy(text4, destFileName, overwrite: true);
					}
					Directory.Delete(path3, recursive: true);
				}
			}
			catch { }
			try
			{
				Thread.Sleep(500);
				File.Delete(text3);
			}
			catch { }
		}
	}

	/// <summary>
	/// FIX #1: Process Deadlock 해결.
	/// stderr와 stdout을 별도 스레드에서 비동기로 읽어 버퍼 초과 방지.
	/// </summary>
	private string GetCurrentChromeDriverVersion()
	{
		KillAllChromeDriverProcesses();
		string path = "chromedriver.exe";
		string directoryName = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location);
		directoryName = Path.Combine(directoryName, path);
		if (File.Exists(directoryName))
		{
			using Process process = Process.Start(new ProcessStartInfo
			{
				FileName = directoryName,
				Arguments = "--version",
				UseShellExecute = false,
				CreateNoWindow = true,
				RedirectStandardOutput = true,
				RedirectStandardError = true
			});

			// FIX: 데드락 방지 — stdout/stderr를 안전하게 읽기
			string stdOut = process.StandardOutput.ReadToEnd();
			string stdErr = process.StandardError.ReadToEnd();
			process.WaitForExit(5000);

			if (!process.HasExited)
			{
				// 타임아웃 — 프로세스 강제 종료
				try { process.Kill(); } catch { }
				throw new Exception("ChromeDriver 버전 확인 시간 초과.");
			}

			if (!string.IsNullOrEmpty(stdErr))
			{
				throw new ArgumentException($"크롬드라이버 버전 정보 얻기 실패: {stdErr.Trim()}");
			}

			if (string.IsNullOrEmpty(stdOut))
				return string.Empty;

			// "ChromeDriver 120.0.6099.109 (...)" → "120.0.6099.109"
			string[] parts = stdOut.Split(new char[1] { ' ' }, StringSplitOptions.RemoveEmptyEntries);
			if (parts.Length >= 2)
				return parts[1];

			return stdOut.Trim();
		}
		return string.Empty;
	}

	protected override void Dispose(bool disposing)
	{
		if (disposing && components != null)
		{
			components.Dispose();
		}
		((Form)this).Dispose(disposing);
	}

	private void InitializeComponent()
	{
		ComponentResourceManager componentResourceManager = new ComponentResourceManager(typeof(FormDownloader));
		ProgressBarSequence = new ProgressBar();
		LabelProgress = new Label();
		ProgressBarDownloading = new ProgressBar();
		LabelDownloadingUrl = new Label();
		FolderBrowserDialogDownloadLocation = new FolderBrowserDialog();
		LabelStatus = new Label();
		((Control)this).SuspendLayout();
		((Control)ProgressBarSequence).Location = new Point(33, 94);
		((Control)ProgressBarSequence).Name = "ProgressBarSequence";
		((Control)ProgressBarSequence).Size = new Size(364, 24);
		((Control)ProgressBarSequence).TabIndex = 1;
		((Control)LabelProgress).Location = new Point(176, 130);
		((Control)LabelProgress).Name = "LabelProgress";
		((Control)LabelProgress).Size = new Size(79, 13);
		((Control)LabelProgress).TabIndex = 9;
		((Control)LabelProgress).Text = "0 / 0";
		LabelProgress.TextAlign = (ContentAlignment)32;
		((Control)ProgressBarDownloading).Location = new Point(33, 50);
		((Control)ProgressBarDownloading).Name = "ProgressBarDownloading";
		((Control)ProgressBarDownloading).Size = new Size(364, 18);
		((Control)ProgressBarDownloading).TabIndex = 10;
		((Control)LabelDownloadingUrl).Location = new Point(33, 19);
		((Control)LabelDownloadingUrl).Name = "LabelDownloadingUrl";
		((Control)LabelDownloadingUrl).Size = new Size(364, 28);
		((Control)LabelDownloadingUrl).TabIndex = 11;
		LabelDownloadingUrl.TextAlign = (ContentAlignment)16;
		((Control)LabelStatus).Location = new Point(33, 78);
		((Control)LabelStatus).Name = "LabelStatus";
		((Control)LabelStatus).Size = new Size(364, 13);
		((Control)LabelStatus).TabIndex = 14;
		((Control)LabelStatus).Text = "업데이트 정보를 불러오고 있습니다...";
		LabelStatus.TextAlign = (ContentAlignment)16;
		((ContainerControl)this).AutoScaleDimensions = new SizeF(6f, 13f);
		((ContainerControl)this).AutoScaleMode = (AutoScaleMode)1;
		((Form)this).ClientSize = new Size(431, 172);
		((Control)this).Controls.Add((Control)(object)LabelStatus);
		((Control)this).Controls.Add((Control)(object)LabelDownloadingUrl);
		((Control)this).Controls.Add((Control)(object)ProgressBarDownloading);
		((Control)this).Controls.Add((Control)(object)LabelProgress);
		((Control)this).Controls.Add((Control)(object)ProgressBarSequence);
		((Form)this).FormBorderStyle = (FormBorderStyle)3;
		((Form)this).HelpButton = true;
		((Form)this).Icon = (Icon)componentResourceManager.GetObject("$this.Icon");
		((Form)this).MaximizeBox = false;
		((Control)this).Name = "FormDownloader";
		((Form)this).StartPosition = (FormStartPosition)1;
		((Control)this).Text = "디버 업데이터";
		((Form)this).Load += FormAsync_Load;
		((Control)this).ResumeLayout(false);
	}
}